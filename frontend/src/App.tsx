import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode'
import { api } from './api'
import type { RegisteredInvoice, ResolvedInvoiceData } from './types'

type EntryMode = 'scan' | 'manual'
type PromoStep = 1 | 2 | 3

const QR_READER_ELEMENT_ID = 'dgi-qr-reader'
const CUFE_SHORT_PREFIX = 'FE01200000032812-2-249262-'
const INVOICE_SCANNER_FORMATS = [
  Html5QrcodeSupportedFormats.QR_CODE,
  Html5QrcodeSupportedFormats.DATA_MATRIX,
  Html5QrcodeSupportedFormats.PDF_417,
  Html5QrcodeSupportedFormats.AZTEC,
]

interface InvoiceFormState {
  rawInput: string
  invoice_number: string
  purchase_amount: string
  issued_at: string
  issuer_name: string
  cufe_tail: string
  document_type: 'cedula' | 'passport' | 'residente'
  document_number: string
  first_name: string
  last_name: string
  phone: string
  email: string
}

function emptyForm(): InvoiceFormState {
  return {
    rawInput: '',
    invoice_number: '',
    purchase_amount: '',
    issued_at: '',
    issuer_name: '',
    cufe_tail: '',
    document_type: 'cedula',
    document_number: '',
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
  }
}

function createInvoiceScanner() {
  return new Html5Qrcode(QR_READER_ELEMENT_ID, {
    verbose: false,
    formatsToSupport: INVOICE_SCANNER_FORMATS,
  })
}

export function App() {
  const [entryMode, setEntryMode] = useState<EntryMode>('scan')
  const [promoStep, setPromoStep] = useState<PromoStep>(1)
  const [invoiceForm, setInvoiceForm] = useState<InvoiceFormState>(emptyForm())
  const [scannerOn, setScannerOn] = useState(false)
  const [scannerError, setScannerError] = useState<string | null>(null)
  const [resolvingInvoice, setResolvingInvoice] = useState(false)
  const [submitError, setSubmitError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [resolvedInvoiceData, setResolvedInvoiceData] = useState<ResolvedInvoiceData | null>(null)
  const [invoiceValidated, setInvoiceValidated] = useState(false)
  const [manualTouched, setManualTouched] = useState(false)

  const steps = useMemo(
    () => [
      { id: 1, title: 'Escanea o ingresa CUFE' },
      { id: 2, title: 'Factura valida' },
      { id: 3, title: 'Completa tu registro' },
    ],
    [],
  )

  useEffect(() => {
    if (!scannerOn || entryMode !== 'scan') return undefined

    let scanner: Html5Qrcode | null = null
    let stopped = false

    async function start() {
      try {
        scanner = createInvoiceScanner()
        await scanner.start({ facingMode: 'environment' }, { fps: 12, disableFlip: true }, async (decodedText) => {
          if (stopped) return
          await resolveInvoice(decodedText)
          await scanner?.stop().catch(() => undefined)
          await scanner?.clear()
          setScannerOn(false)
        }, () => undefined)
      } catch (error) {
        setScannerError(normalizeError(error))
      }
    }

    void start()

    return () => {
      stopped = true
      if (!scanner) return
      void scanner.stop().catch(() => undefined).finally(() => {
        void scanner?.clear()
      })
    }
  }, [entryMode, scannerOn])

  async function resolveInvoice(rawText: string) {
    try {
      setResolvingInvoice(true)
      const response = await api.post<{ data: ResolvedInvoiceData & { is_valid?: boolean; minimum_amount?: number } }>('/invoices/resolve', {
        qr_raw_text: rawText,
      })

      setScannerError(null)

      if (!response.data.data.is_valid) {
        setResolvedInvoiceData(null)
        setInvoiceValidated(false)
        setSubmitError(null)
        setPromoStep(1)
        setScannerError(`La factura no supera el monto minimo de $${(response.data.data.minimum_amount ?? 25).toFixed(2)}.`)
        return
      }

      setResolvedInvoiceData(response.data.data)
      setInvoiceValidated(true)
      setPromoStep(2)
      setInvoiceForm((current) => ({
        ...current,
        rawInput: rawText,
        cufe_tail: rawText.slice(-60),
        invoice_number: response.data.data.invoice_number ?? current.invoice_number,
        purchase_amount: response.data.data.purchase_amount ?? current.purchase_amount,
        issued_at: response.data.data.issued_at ?? current.issued_at,
        issuer_name: response.data.data.issuer_name ?? current.issuer_name,
      }))
    } catch (error) {
      setScannerError(normalizeError(error))
    } finally {
      setResolvingInvoice(false)
    }
  }

  async function validateManualCufe() {
    const rawTail = invoiceForm.cufe_tail.trim().replace(/\D/g, '').slice(0, 60)
    if (!rawTail) return

    const rawText = `${CUFE_SHORT_PREFIX}${rawTail}`
    await resolveInvoice(rawText)
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSubmitting(true)
    setSubmitError(null)

    try {
      const rawText = invoiceForm.rawInput || `${CUFE_SHORT_PREFIX}${invoiceForm.cufe_tail}`
      const fullName = `${invoiceForm.first_name.trim()} ${invoiceForm.last_name.trim()}`.trim()
      const response = await api.post<{ invoice: RegisteredInvoice; message?: string }>('/invoices/scan', {
        qr_raw_text: rawText,
        purchase_amount: Number(invoiceForm.purchase_amount || 0),
        invoice_number: invoiceForm.invoice_number || null,
        issued_at: invoiceForm.issued_at || null,
        document_type: invoiceForm.document_type,
        document_number: invoiceForm.document_number,
        first_name: invoiceForm.first_name,
        last_name: invoiceForm.last_name,
        full_name: fullName,
        cedula: invoiceForm.document_number,
        phone: invoiceForm.phone || null,
        email: invoiceForm.email || null,
      })

      setResolvedInvoiceData({
        cufe: response.data.invoice.cufe,
        invoice_number: response.data.invoice.invoice_number ?? response.data.invoice.cufe,
        purchase_amount: String(response.data.invoice.purchase_amount),
        issued_at: response.data.invoice.issued_at ?? '',
        issuer_name: response.data.invoice.issuer_name ?? undefined,
      })
      setInvoiceValidated(true)
      setPromoStep(3)
    } catch (error) {
      setSubmitError(normalizeError(error))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="promo-shell">
      <div className="promo-ambient" />
      <main className="promo-layout">
        <section className="promo-hero">
          <div className="promo-hero-copy">
            <div className="promo-brand-mark">
              <img src="/auth-slogan.webp" alt="Super Carnes" />
            </div>
            <p className="promo-kicker">Super Carnes 2026</p>
            {invoiceValidated ? <p className="promo-valid-badge">Factura válida</p> : null}
            <h1>
              Registra tu
              <span>factura</span>
            </h1>
            <p>
              Escanea el QR o ingresa el CUFE de tu factura para validar la compra y abrir el formulario de participación.
            </p>

            <div className="promo-stepper">
              {steps.map((step) => (
                <div key={step.id} className={`promo-step ${promoStep >= step.id ? 'is-active' : ''}`}>
                  <span>{step.id}</span>
                  <strong>{step.title}</strong>
                </div>
              ))}
            </div>
          </div>

          <div className="promo-art">
            <div className="promo-paper">
              <span>Factura DGI</span>
              <div className="promo-paper-lines">
                <i />
                <i />
                <i />
                <i />
              </div>
            </div>
            <div className="promo-dad">
              <img src="/gaby-torres-celebration.webp" alt="Papa celebrando la promo" />
            </div>
            <div className="promo-ball" aria-hidden="true">
              <img src="/trionda-ball.svg" alt="" />
            </div>
          </div>
        </section>

        <aside className="promo-panel">
          <div className="promo-panel-card promo-panel-highlight">
            <p className="promo-panel-label">Marcador de facturas</p>
            <h2>Participacion activa</h2>
            <p>{invoiceValidated ? 'La factura paso y el formulario ya esta disponible.' : 'Escanea tu primera factura DGI para abrir el formulario.'}</p>
          </div>

          {!invoiceValidated ? (
            <div className="promo-panel-card">
              <div className="promo-mode-switch">
                <button className={entryMode === 'scan' ? 'is-active' : ''} type="button" onClick={() => setEntryMode('scan')}>
                  Escanear QR
                </button>
                <button className={entryMode === 'manual' ? 'is-active' : ''} type="button" onClick={() => setEntryMode('manual')}>
                  Ingresar CUFE
                </button>
              </div>

              {entryMode === 'scan' ? (
                <div className="promo-scan">
                  {!scannerOn ? (
                    <button className="promo-primary" type="button" onClick={() => setScannerOn(true)}>
                      Activar escaneo de QR
                    </button>
                  ) : (
                    <div id={QR_READER_ELEMENT_ID} className="promo-scanner" />
                  )}
                  {scannerError ? <div className="promo-alert">{scannerError}</div> : null}
                </div>
              ) : (
                <div className="promo-manual">
                  <label>
                    Ultimos 60 numeros del CUFE
                    <input
                      value={invoiceForm.cufe_tail}
                      onChange={(e) => {
                        setManualTouched(true)
                        setInvoiceForm((current) => ({
                          ...current,
                          cufe_tail: e.target.value.replace(/\D/g, '').slice(0, 60),
                        }))
                      }}
                      maxLength={60}
                      inputMode="numeric"
                      placeholder="Escribe solo los ultimos 60 numeros"
                    />
                  </label>
                  <button className="promo-primary" type="button" disabled={invoiceForm.cufe_tail.length < 10} onClick={() => void validateManualCufe()}>
                    {resolvingInvoice ? 'Validando...' : 'Validar CUFE'}
                  </button>
                  {scannerError ? <div className="promo-alert">{scannerError}</div> : null}
                  {resolvingInvoice ? (
                    <div className="promo-loading" role="status" aria-live="polite">
                      <span className="promo-spinner" />
                      <span>Estamos validando la factura</span>
                    </div>
                  ) : !scannerError ? (
                    <p className="promo-help">{manualTouched ? 'Listo. Espera un momento.' : 'Escribe los ultimos 60 numeros del CUFE y toca validar.'}</p>
                  ) : null}
                </div>
              )}
            </div>
          ) : (
            <form className="promo-panel-card promo-form promo-form-compact" onSubmit={handleSubmit}>
              <div className="promo-form-head">
                <p>Factura validada</p>
                <h3>Completa tu registro</h3>
              </div>

              <div className="promo-form-grid">
                <label>
                  Tipo de documento
                  <select
                    className="promo-select"
                    value={invoiceForm.document_type}
                    onChange={(e) => setInvoiceForm((current) => ({ ...current, document_type: e.target.value as InvoiceFormState['document_type'], document_number: '' }))}
                    required
                  >
                    <option value="cedula">Cédula</option>
                    <option value="passport">Pasaporte</option>
                    <option value="residente">Carnet de residente</option>
                  </select>
                </label>
                <label>
                  N° documento
                  <input
                    value={invoiceForm.document_number}
                    onChange={(e) => setInvoiceForm((current) => ({
                      ...current,
                      document_number: sanitizeDocumentNumber(current.document_type, e.target.value),
                    }))}
                    placeholder={documentPlaceholder(invoiceForm.document_type)}
                    required
                  />
                </label>
                <label>
                  Nombre
                  <input
                    value={invoiceForm.first_name}
                    onChange={(e) => setInvoiceForm((current) => ({ ...current, first_name: sanitizeName(e.target.value) }))}
                    placeholder="Nombre(s)"
                    required
                  />
                </label>
                <label>
                  Apellidos
                  <input
                    value={invoiceForm.last_name}
                    onChange={(e) => setInvoiceForm((current) => ({ ...current, last_name: sanitizeName(e.target.value) }))}
                    placeholder="Apellido(s)"
                    required
                  />
                </label>
                <label>
                  Telefono
                  <input
                    value={invoiceForm.phone}
                    onChange={(e) => setInvoiceForm((current) => ({ ...current, phone: sanitizePanamaPhone(e.target.value) }))}
                    placeholder="6XXX-XXXX"
                    inputMode="tel"
                    required
                  />
                </label>
                <label>
                  Correo
                  <input
                    value={invoiceForm.email}
                    onChange={(e) => setInvoiceForm((current) => ({ ...current, email: e.target.value.trim() }))}
                    type="email"
                    placeholder="correo@dominio.com"
                    required
                  />
                </label>
                <label className="promo-form-wide">
                  Ultimos 60 numeros del CUFE
                  <input
                    value={invoiceForm.cufe_tail}
                    onChange={(e) => setInvoiceForm((current) => ({
                      ...current,
                      cufe_tail: e.target.value.replace(/\D/g, '').slice(0, 60),
                    }))}
                    maxLength={60}
                    inputMode="numeric"
                    placeholder="Escribe solo los ultimos 60 numeros"
                    required
                  />
                </label>
              </div>

              {submitError ? <div className="promo-alert">{submitError}</div> : null}
              <button className="promo-primary" type="submit" disabled={submitting}>
                {submitting ? 'Enviando...' : 'Registrar ahora'}
              </button>
            </form>
          )}

          {resolvedInvoiceData ? (
            <div className="promo-panel-card promo-last">
              <p className="promo-panel-label">Ultima validacion</p>
            </div>
          ) : null}
        </aside>
      </main>
    </div>
  )
}

function documentPlaceholder(type: InvoiceFormState['document_type']) {
  switch (type) {
    case 'passport':
      return 'Pasaporte'
    case 'residente':
      return 'Carnet / residente'
    default:
      return '8-888-8888'
  }
}

function sanitizeDocumentNumber(type: InvoiceFormState['document_type'], value: string) {
  const raw = value.trim().toUpperCase()
  if (type === 'passport' || type === 'residente') {
    return raw.replace(/[^A-Z0-9-]/g, '').slice(0, 40)
  }
  return raw.replace(/[^0-9-]/g, '').slice(0, 20)
}

function sanitizeName(value: string) {
  return value
    .replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s'-]/g, '')
    .replace(/\s{2,}/g, ' ')
    .trimStart()
}

function sanitizePanamaPhone(value: string) {
  const cleaned = value.replace(/\D/g, '').slice(0, 8)
  if (cleaned.length <= 4) return cleaned
  return `${cleaned.slice(0, 4)}-${cleaned.slice(4)}`
}

function normalizeError(error: unknown) {
  if (typeof error !== 'object' || !error) return 'Ocurrio un error inesperado.'
  const candidate = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
  const firstError = candidate.response?.data?.errors ? Object.values(candidate.response.data.errors)[0]?.[0] : null
  return firstError ?? candidate.response?.data?.message ?? 'Ocurrio un error inesperado.'
}
