import { useEffect, useMemo, useRef, useState } from 'react'
import type { FormEvent } from 'react'
import { Html5Qrcode, Html5QrcodeSupportedFormats, type Html5QrcodeCameraScanConfig } from 'html5-qrcode'
import { api, setApiToken } from './api'
import type { RegisteredInvoice, ResolvedInvoiceData, User } from './types'

type EntryMode = 'scan' | 'manual' | 'whatsapp'
type PromoStep = 1 | 2 | 3
type CampaignStatus = 'draft' | 'active' | 'paused' | 'archived'
type ParticipationMode = 'points' | 'threshold_form'

interface Campaign {
  id: number
  name: string
  slug: string
  description?: string | null
  status: CampaignStatus
  participation_mode?: ParticipationMode | null
  entry_threshold_amount?: number | string | null
  is_listed?: boolean
  hero_image_url?: string | null
  card_image_url?: string | null
}

interface BranchOption {
  id: number
  name: string
  code: string
}

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
  nearest_branch_id: string
  entrepreneur_name: string
  entrepreneur_province: string
  entrepreneur_type: string
  entrepreneur_story: string
  entrepreneur_reason: string
}

const QR_READER_ELEMENT_ID = 'dgi-qr-reader'
const CUFE_SHORT_PREFIX = 'FE01200000032812-2-249262-'
const CUFE_SHORT_PREFIX_ABBR = 'FE...-249262-'
const CUFE_TAIL_EXAMPLE = '630003202607091500223344556677889900112233445566778899001122'.slice(0, 60)
const AUTH_TOKEN_STORAGE_KEY = 'supercarnes.auth.token'
const PANAMA_PROVINCES = [
  'Bocas del Toro',
  'Cocle',
  'Colon',
  'Chiriqui',
  'Darien',
  'Herrera',
  'Los Santos',
  'Panama',
  'Panama Oeste',
  'Veraguas',
]
const INVOICE_SCANNER_FORMATS = [
  Html5QrcodeSupportedFormats.QR_CODE,
  Html5QrcodeSupportedFormats.DATA_MATRIX,
  Html5QrcodeSupportedFormats.PDF_417,
  Html5QrcodeSupportedFormats.AZTEC,
]
const INVOICE_SCANNER_START_CONFIG: Html5QrcodeCameraScanConfig = {
  fps: 12,
  disableFlip: true,
  aspectRatio: 4 / 3,
  qrbox: (viewfinderWidth, viewfinderHeight) => {
    const edge = Math.floor(Math.max(190, Math.min(viewfinderWidth, viewfinderHeight) * 0.78))

    return {
      width: Math.min(edge, 330),
      height: Math.min(edge, 330),
    }
  },
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
    nearest_branch_id: '',
    entrepreneur_name: '',
    entrepreneur_province: '',
    entrepreneur_type: '',
    entrepreneur_story: '',
    entrepreneur_reason: '',
  }
}

function firstNameFromUser(user: User): string {
  return (user.full_name || '').trim().split(/\s+/)[0] || 'Participante'
}

function lastNameFromUser(user: User): string {
  const parts = (user.full_name || '').trim().split(/\s+/)
  return parts.slice(1).join(' ') || '.'
}

function formFromUser(user: User): InvoiceFormState {
  return {
    ...emptyForm(),
    document_type: user.document_type,
    document_number: user.cedula,
    first_name: firstNameFromUser(user),
    last_name: lastNameFromUser(user),
    phone: user.phone ?? '',
    email: user.email,
    entrepreneur_name: user.entrepreneur_name ?? '',
    entrepreneur_province: user.entrepreneur_province ?? '',
    entrepreneur_reason: user.entrepreneur_reason ?? '',
  }
}

function createInvoiceScanner() {
  return new Html5Qrcode(QR_READER_ELEMENT_ID, {
    verbose: false,
    formatsToSupport: INVOICE_SCANNER_FORMATS,
  })
}

export function App() {
  const [campaigns, setCampaigns] = useState<Campaign[]>([])
  const [loadingCampaigns, setLoadingCampaigns] = useState(true)
  const [campaignError, setCampaignError] = useState<string | null>(null)
  const [authUser, setAuthUser] = useState<User | null>(null)
  const [authLoading, setAuthLoading] = useState(true)
  const [path, setPath] = useState(window.location.pathname)

  useEffect(() => {
    const token = window.localStorage.getItem(AUTH_TOKEN_STORAGE_KEY)
    if (!token) {
      setAuthLoading(false)
      return
    }

    setApiToken(token)
    let cancelled = false
    api
      .get<{ data: User }>('/auth/me')
      .then((response) => {
        if (!cancelled) setAuthUser(response.data.data)
      })
      .catch(() => {
        window.localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY)
        setApiToken(null)
      })
      .finally(() => {
        if (!cancelled) setAuthLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [])

  useEffect(() => {
    const onPopState = () => setPath(window.location.pathname)
    window.addEventListener('popstate', onPopState)
    return () => window.removeEventListener('popstate', onPopState)
  }, [])

  useEffect(() => {
    let cancelled = false
    async function loadCampaigns() {
      try {
        setLoadingCampaigns(true)
        const response = await api.get<{ data: Campaign[] }>('/campaigns')
        if (!cancelled) {
          setCampaigns(response.data.data ?? [])
          setCampaignError(null)
        }
      } catch (error) {
        if (!cancelled) setCampaignError(normalizeError(error))
      } finally {
        if (!cancelled) setLoadingCampaigns(false)
      }
    }

    void loadCampaigns()
    return () => {
      cancelled = true
    }
  }, [])

  const selectedSlug = useMemo(() => {
    const current = path.replace(/\/+$/, '')
    if (!current || current === '/') return null
    return current.split('/').filter(Boolean)[0] ?? null
  }, [path])

  const selectedCampaign = useMemo(() => campaigns.find((campaign) => campaign.slug === selectedSlug) ?? null, [campaigns, selectedSlug])

  if (selectedSlug && !selectedCampaign && !loadingCampaigns) {
    return <PromoNotFound onBack={() => goHome(setPath)} />
  }

  if (!selectedSlug) {
    if (authLoading) {
      return <AuthLoadingScreen />
    }

    if (!authUser) {
      return <ParticipantAuthScreen onAuthenticated={setAuthUser} />
    }

    return (
      <PromoCatalog
        campaigns={campaigns}
        loading={loadingCampaigns}
        error={campaignError}
        user={authUser}
        onLogout={() => void logoutParticipant(setAuthUser)}
        onOpen={(slug) => openPath(`/${slug}`, setPath)}
      />
    )
  }

  if (selectedCampaign?.participation_mode === 'threshold_form') {
    if (authLoading) {
      return <AuthLoadingScreen />
    }

    if (!authUser) {
      return <ParticipantAuthScreen onAuthenticated={setAuthUser} />
    }

    return <ThresholdPromoLanding campaign={selectedCampaign} user={authUser} onUserChange={setAuthUser} onBack={() => goHome(setPath)} />
  }

  return <PromoLanding campaign={selectedCampaign} onBack={() => goHome(setPath)} />
}

async function logoutParticipant(setAuthUser: (user: User | null) => void) {
  try {
    await api.post('/auth/logout')
  } catch {
    // Local logout should still work if the token already expired.
  } finally {
    window.localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY)
    setApiToken(null)
    setAuthUser(null)
  }
}

function goHome(setPath: (value: string) => void) {
  window.history.pushState({}, '', '/')
  setPath(window.location.pathname)
}

function openPath(path: string, setPath: (value: string) => void) {
  window.history.pushState({}, '', path)
  setPath(window.location.pathname)
}

function AuthLoadingScreen() {
  return (
    <div className="participant-auth">
      <div className="participant-auth-card participant-auth-card-slim">
        <img src="/logo_web.jpg" alt="Super Carnes" />
        <h1>Cargando tu sesion...</h1>
        <p>Estamos preparando tus promociones.</p>
      </div>
    </div>
  )
}

function ParticipantAuthScreen({ onAuthenticated }: { onAuthenticated: (user: User) => void }) {
  const [mode, setMode] = useState<'login' | 'register'>('login')
  const [form, setForm] = useState({
    document_type: 'cedula' as InvoiceFormState['document_type'],
    document_number: '',
    full_name: '',
    email: '',
    phone: '',
    password: '',
    login: '',
  })
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSubmitting(true)
    setError(null)

    try {
      const endpoint = mode === 'login' ? '/auth/login' : '/auth/register'
      const payload = mode === 'login'
        ? { login: form.login.trim(), password: form.password }
        : {
            document_type: form.document_type,
            document_number: sanitizeDocumentNumber(form.document_type, form.document_number),
            full_name: sanitizeName(form.full_name),
            email: form.email.trim(),
            phone: sanitizePanamaPhone(form.phone),
            password: form.password,
          }
      const response = await api.post<{ token: string; data: User }>(endpoint, payload)
      window.localStorage.setItem(AUTH_TOKEN_STORAGE_KEY, response.data.token)
      setApiToken(response.data.token)
      onAuthenticated(response.data.data)
    } catch (authError) {
      setError(normalizeError(authError))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="participant-auth">
      <section className="participant-auth-hero">
        <img src="/logo_web.jpg" alt="Super Carnes" />
        <h1>Promos Super Carnes</h1>
        <p>Ingresa, elige una promo activa y registra tus facturas con tu mismo documento.</p>
        <div className="participant-auth-steps">
          <span>1. Inicia sesion</span>
          <span>2. Elige promo</span>
          <span>3. Sube facturas</span>
        </div>
      </section>
      <form className="participant-auth-card" onSubmit={handleSubmit}>
        <div className="participant-auth-tabs">
          <button type="button" className={mode === 'login' ? 'is-active' : ''} onClick={() => setMode('login')}>Iniciar sesion</button>
          <button type="button" className={mode === 'register' ? 'is-active' : ''} onClick={() => setMode('register')}>Registrarme</button>
        </div>

        {mode === 'login' ? (
          <>
            <label>
              Correo o cedula
              <input value={form.login} onChange={(event) => setForm((current) => ({ ...current, login: event.target.value }))} placeholder="correo@dominio.com o 8-123-456" required />
            </label>
            <label>
              Contrasena
              <input value={form.password} onChange={(event) => setForm((current) => ({ ...current, password: event.target.value }))} type="password" minLength={6} required />
            </label>
          </>
        ) : (
          <>
            <label>
              Nombre completo
              <input value={form.full_name} onChange={(event) => setForm((current) => ({ ...current, full_name: sanitizeName(event.target.value) }))} placeholder="Nombre y apellido" required />
            </label>
            <div className="participant-auth-grid">
              <label>
                Documento
                <select value={form.document_type} onChange={(event) => setForm((current) => ({ ...current, document_type: event.target.value as InvoiceFormState['document_type'], document_number: '' }))}>
                  <option value="cedula">Cedula</option>
                  <option value="passport">Pasaporte</option>
                  <option value="residente">Carnet residente</option>
                </select>
              </label>
              <label>
                Numero
                <input value={form.document_number} onChange={(event) => setForm((current) => ({ ...current, document_number: sanitizeDocumentNumber(current.document_type, event.target.value) }))} placeholder={documentPlaceholder(form.document_type)} required />
              </label>
            </div>
            <label>
              Correo
              <input value={form.email} onChange={(event) => setForm((current) => ({ ...current, email: event.target.value.trim() }))} type="email" placeholder="correo@dominio.com" required />
            </label>
            <label>
              Telefono
              <input value={form.phone} onChange={(event) => setForm((current) => ({ ...current, phone: sanitizePanamaPhone(event.target.value) }))} inputMode="tel" placeholder="6XXX-XXXX" required />
            </label>
            <label>
              Contrasena
              <input value={form.password} onChange={(event) => setForm((current) => ({ ...current, password: event.target.value }))} type="password" minLength={6} required />
            </label>
          </>
        )}

        {error ? <div className="promo-alert">{error}</div> : null}
        <button className="promo-primary" type="submit" disabled={submitting}>
          {submitting ? 'Procesando...' : mode === 'login' ? 'Entrar' : 'Crear cuenta'}
        </button>
      </form>
    </div>
  )
}

function PromoCatalog({
  campaigns,
  loading,
  error,
  user,
  onLogout,
  onOpen,
}: {
  campaigns: Campaign[]
  loading: boolean
  error: string | null
  user: User
  onLogout: () => void
  onOpen: (slug: string) => void
}) {
  return (
    <div className="promo-shell promo-shell-catalog">
      <div className="promo-ambient" />
      <main className="promo-catalog-layout">
        <header className="promo-catalog-header">
          <div className="promo-catalog-copy">
            <img src="/logo_web.jpg" alt="Super Carnes" />
          <p className="promo-kicker">Promociones Super Carnes</p>
          <h1>Elige una promo activa</h1>
          <p>Participa en las promociones vigentes: registra tus facturas, sigue tu progreso y entérate cuando ganes.</p>
          </div>
          <div className="promo-session">
            <strong>{user.full_name}</strong>
            <span>{user.cedula}</span>
            <button type="button" onClick={onLogout}>Cerrar sesion</button>
          </div>
        </header>

        {loading ? <div className="promo-state">Cargando promociones...</div> : null}
        {error ? <div className="promo-alert">{error}</div> : null}

        {!loading && campaigns.length === 0 ? (
          <div className="promo-state">No hay promociones visibles por ahora.</div>
        ) : (
          <section className="promo-grid">
            {campaigns.map((campaign) => (
              <button key={campaign.id} type="button" className="promo-card" onClick={() => onOpen(campaign.slug)}>
                <div className="promo-card-image">
                  {campaign.card_image_url ? <img src={campaign.card_image_url} alt={campaign.name} loading="lazy" /> : null}
                </div>
                <div className="promo-card-body">
                  <span>{campaign.participation_mode === 'threshold_form' ? `Meta: $${Number(campaign.entry_threshold_amount ?? 300).toFixed(0)} en facturas` : campaign.status === 'active' ? 'Activa' : 'Disponible'}</span>
                  <strong>{campaign.name}</strong>
                  <p>{campaign.description ?? 'Abre esta promoción para participar.'}</p>
                  <em>Participar ahora →</em>
                </div>
              </button>
            ))}
          </section>
        )}
      </main>
    </div>
  )
}

function PromoLanding({
  campaign,
  onBack,
}: {
  campaign: Campaign | null
  onBack: () => void
}) {
  const [entryMode, setEntryMode] = useState<EntryMode>('scan')
  const [promoStep, setPromoStep] = useState<PromoStep>(1)
  const [invoiceForm, setInvoiceForm] = useState<InvoiceFormState>(emptyForm())
  const [scannerOn, setScannerOn] = useState(false)
  const [scannerError, setScannerError] = useState<string | null>(null)
  const [resolvingInvoice, setResolvingInvoice] = useState(false)
  const [submitError, setSubmitError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [invoiceValidated, setInvoiceValidated] = useState(false)
  const [manualTouched, setManualTouched] = useState(false)
  const [campaignTotal, setCampaignTotal] = useState(0)
  const [campaignThreshold] = useState(campaign?.slug === 'del-sueno-al-puesto' ? 300 : 0)

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
        await scanner.start({ facingMode: 'environment' }, INVOICE_SCANNER_START_CONFIG, async (decodedText) => {
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
        setInvoiceValidated(false)
        setSubmitError(null)
        setPromoStep(1)
        setScannerError(`La factura no supera el monto minimo de $${(response.data.data.minimum_amount ?? 25).toFixed(2)}.`)
        return
      }

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
    await resolveInvoice(`${CUFE_SHORT_PREFIX}${rawTail}`)
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSubmitting(true)
    setSubmitError(null)

    try {
      const rawText = invoiceForm.rawInput || `${CUFE_SHORT_PREFIX}${invoiceForm.cufe_tail}`
      const fullName = `${invoiceForm.first_name.trim()} ${invoiceForm.last_name.trim()}`.trim()
      const response = await api.post<{ invoice: RegisteredInvoice; message?: string; campaign_total?: number; campaign_threshold?: number }>('/invoices/scan', {
        qr_raw_text: rawText,
        campaign_slug: campaign?.slug ?? null,
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
        nearest_branch_id: invoiceForm.nearest_branch_id ? Number(invoiceForm.nearest_branch_id) : null,
        entrepreneur_name: invoiceForm.entrepreneur_name || null,
        entrepreneur_province: invoiceForm.entrepreneur_province || null,
        entrepreneur_type: invoiceForm.entrepreneur_type || null,
        entrepreneur_story: invoiceForm.entrepreneur_story || null,
        entrepreneur_reason: invoiceForm.entrepreneur_reason || null,
      })

      if (typeof response.data.campaign_total === 'number') {
        setCampaignTotal(response.data.campaign_total)
      }
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
            <button className="promo-back" type="button" onClick={onBack}>
              Volver a promociones
            </button>
            <p className="promo-kicker">/{campaign?.slug ?? 'promo'}</p>
            {invoiceValidated ? <p className="promo-valid-badge">Factura valida</p> : null}
            <h1>
              {campaign?.name ?? 'Promocion'}
              <span>Super Carnes</span>
            </h1>
            <p>{campaign?.description ?? 'Ingresa a la promoción y registra tu factura.'}</p>
            {campaign?.slug === 'del-sueno-al-puesto' ? (
              <div className="promo-tracker">
                <div className="promo-tracker-head">
                  <strong>Acumulado</strong>
                  <span>${Math.min(campaignTotal, campaignThreshold || 300).toFixed(2)} / ${(campaignThreshold || 300).toFixed(2)}</span>
                </div>
                <div className="promo-tracker-bar">
                  <i style={{ width: `${Math.min(100, ((campaignTotal / (campaignThreshold || 300)) * 100))}%` }} />
                </div>
                <p>{campaignTotal >= (campaignThreshold || 300) ? 'Ya puedes participar en la selección principal.' : `Te faltan $${Math.max((campaignThreshold || 300) - campaignTotal, 0).toFixed(2)} para llegar al límite.`}</p>
              </div>
            ) : null}
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
              <span>Promo activa</span>
              <div className="promo-paper-lines">
                <i />
                <i />
                <i />
                <i />
              </div>
            </div>
            <div className="promo-dad">
              <img src={campaign?.hero_image_url || '/gaby-torres-celebration.webp'} alt={campaign?.name ?? 'Promocion'} />
            </div>
            <div className="promo-ball" aria-hidden="true">
              <img src="/trionda-ball.svg" alt="" />
            </div>
          </div>
        </section>

        <aside className="promo-panel">
          <div className="promo-panel-card promo-panel-highlight">
            <p className="promo-panel-label">Promoción activa</p>
            <h2>{campaign?.status === 'active' ? 'Lista para participar' : 'Promoción disponible'}</h2>
            <p>{invoiceValidated ? 'La factura pasó y el formulario ya está disponible.' : 'Escanea tu factura DGI para comenzar.'}</p>
          </div>

          {!invoiceValidated ? (
            <div className="promo-panel-card">
              <div className="promo-mode-switch">
                <button className={entryMode === 'scan' ? 'is-active' : ''} type="button" onClick={() => setEntryMode('scan')}>
                  Escanear QR
                </button>
                <button className={entryMode === 'manual' ? 'is-active' : ''} type="button" onClick={() => setEntryMode('manual')}>
                  Manual
                </button>
                <button className={`promo-mode-whatsapp ${entryMode === 'whatsapp' ? 'is-active' : ''}`} type="button" onClick={() => setEntryMode('whatsapp')}>
                  <span className="material-symbols-outlined" aria-hidden="true">chat</span>
                  WhatsApp
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
              ) : entryMode === 'manual' ? (
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
              ) : (
                <div className="promo-whatsapp-tab">
                  <p className="promo-whatsapp-tab-intro">Envía los siguientes datos por WhatsApp para participar:</p>
                  <ul className="promo-whatsapp-steps">
                    <li>
                      <span className="material-symbols-outlined" aria-hidden="true">photo_camera</span>
                      <div>
                        <strong>Fotografía de tu factura</strong>
                        <span>Foto legible de tu compra en Super Carnes</span>
                      </div>
                    </li>
                    <li>
                      <span className="material-symbols-outlined" aria-hidden="true">person</span>
                      <div>
                        <strong>Nombre completo</strong>
                        <span>Tu nombre tal como aparece en tu documento</span>
                      </div>
                    </li>
                    <li>
                      <span className="material-symbols-outlined" aria-hidden="true">badge</span>
                      <div>
                        <strong>Número de cédula</strong>
                        <span>Documento de identidad del participante</span>
                      </div>
                    </li>
                    <li>
                      <span className="material-symbols-outlined" aria-hidden="true">favorite</span>
                      <div>
                        <strong>¿Por qué tu papá merece ganar?</strong>
                        <span>Cuéntanos en tus propias palabras</span>
                      </div>
                    </li>
                  </ul>
                  <a
                    className="promo-whatsapp-btn"
                    href="https://wa.me/50768982167?text=Deseo%20participar%20en%20el%20Give%20Away%20del%20dia%20del%20Padre."
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    <span className="material-symbols-outlined" aria-hidden="true">chat</span>
                    Registrar vía WhatsApp
                  </a>
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
                  <input value={invoiceForm.document_number} onChange={(e) => setInvoiceForm((current) => ({ ...current, document_number: sanitizeDocumentNumber(current.document_type, e.target.value) }))} placeholder={documentPlaceholder(invoiceForm.document_type)} required />
                </label>
                <label>
                  Nombre
                  <input value={invoiceForm.first_name} onChange={(e) => setInvoiceForm((current) => ({ ...current, first_name: sanitizeName(e.target.value) }))} placeholder="Nombre(s)" required />
                </label>
                <label>
                  Apellidos
                  <input value={invoiceForm.last_name} onChange={(e) => setInvoiceForm((current) => ({ ...current, last_name: sanitizeName(e.target.value) }))} placeholder="Apellido(s)" required />
                </label>
                <label>
                  Telefono
                  <input value={invoiceForm.phone} onChange={(e) => setInvoiceForm((current) => ({ ...current, phone: sanitizePanamaPhone(e.target.value) }))} placeholder="6XXX-XXXX" inputMode="tel" required />
                </label>
                <label>
                  Correo
                  <input value={invoiceForm.email} onChange={(e) => setInvoiceForm((current) => ({ ...current, email: e.target.value.trim() }))} type="email" placeholder="correo@dominio.com" required />
                </label>
                {campaign?.slug === 'del-sueno-al-puesto' ? (
                  <>
                    <label>
                      Nombre del emprendimiento
                      <input value={invoiceForm.entrepreneur_name} onChange={(e) => setInvoiceForm((current) => ({ ...current, entrepreneur_name: e.target.value }))} placeholder="Nombre del emprendimiento" required />
                    </label>
                    <label>
                      Provincia del emprendimiento
                      <input value={invoiceForm.entrepreneur_province} onChange={(e) => setInvoiceForm((current) => ({ ...current, entrepreneur_province: e.target.value }))} placeholder="Provincia" required />
                    </label>
                    <label>
                      Sucursal de Super Carnes más cercana
                      <input value={invoiceForm.entrepreneur_type} onChange={(e) => setInvoiceForm((current) => ({ ...current, entrepreneur_type: e.target.value }))} placeholder="Sucursal más cercana" required />
                    </label>
                    <label>
                      Tipo de emprendimiento
                      <input value={invoiceForm.entrepreneur_reason} onChange={(e) => setInvoiceForm((current) => ({ ...current, entrepreneur_reason: e.target.value }))} placeholder="Comida, artesanías, belleza..." required />
                    </label>
                    <label className="promo-form-wide">
                      Historia del emprendimiento
                      <textarea value={invoiceForm.entrepreneur_story} onChange={(e) => setInvoiceForm((current) => ({ ...current, entrepreneur_story: e.target.value }))} rows={4} required />
                    </label>
                    <label className="promo-form-wide">
                      ¿Por qué deben ganar la tolda?
                      <textarea value={invoiceForm.entrepreneur_reason} onChange={(e) => setInvoiceForm((current) => ({ ...current, entrepreneur_reason: e.target.value }))} rows={4} required />
                    </label>
                  </>
                ) : null}
                <label className="promo-form-wide">
                  Ultimos 60 numeros del CUFE
                  <input value={invoiceForm.cufe_tail} onChange={(e) => setInvoiceForm((current) => ({ ...current, cufe_tail: e.target.value.replace(/\D/g, '').slice(0, 60) }))} maxLength={60} inputMode="numeric" placeholder="Escribe solo los ultimos 60 numeros" required />
                </label>
              </div>

              {submitError ? <div className="promo-alert">{submitError}</div> : null}
              <button className="promo-primary" type="submit" disabled={submitting}>
                {submitting ? 'Enviando...' : 'Registrar ahora'}
              </button>
            </form>
          )}
        </aside>
      </main>
    </div>
  )
}

function ThresholdPromoLanding({
  campaign,
  user,
  onUserChange,
  onBack,
}: {
  campaign: Campaign | null
  user: User
  onUserChange: (user: User) => void
  onBack: () => void
}) {
  const [entryMode, setEntryMode] = useState<EntryMode>('manual')
  const [invoiceForm, setInvoiceForm] = useState<InvoiceFormState>(() => formFromUser(user))
  const [profileForm, setProfileForm] = useState({
    entrepreneur_name: user.entrepreneur_name ?? '',
    entrepreneur_province: user.entrepreneur_province ?? '',
    entrepreneur_reason: user.entrepreneur_reason ?? '',
  })
  const [scannerOn, setScannerOn] = useState(false)
  const [scannerError, setScannerError] = useState<string | null>(null)
  const [resolvingInvoice, setResolvingInvoice] = useState(false)
  const [submitError, setSubmitError] = useState<string | null>(null)
  const [submitSuccess, setSubmitSuccess] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [savingProfile, setSavingProfile] = useState(false)
  const [invoiceValidated, setInvoiceValidated] = useState(false)
  const [manualTouched, setManualTouched] = useState(false)
  const [campaignTotal, setCampaignTotal] = useState(0)
  const [animatedTotal, setAnimatedTotal] = useState(0)
  const animatedTotalRef = useRef(0)

  const thresholdAmount = Number(campaign?.entry_threshold_amount ?? 300)
  const campaignQualified = campaignTotal >= thresholdAmount
  const remainingAmount = Math.max(thresholdAmount - campaignTotal, 0)
  const profileCompleted = Boolean(user.entrepreneur_name && user.entrepreneur_province && user.entrepreneur_reason)
  const canSubmitInvoice = profileCompleted && !submitting && (entryMode === 'scan' ? invoiceValidated : invoiceForm.cufe_tail.trim().replace(/\D/g, '').length >= 10)

  const trackerProgress = Math.min(100, (animatedTotal / thresholdAmount) * 100)

  useEffect(() => {
    const from = animatedTotalRef.current
    const to = Math.min(campaignTotal, thresholdAmount)
    const duration = 900
    const start = performance.now()
    let raf = 0

    const tick = (now: number) => {
      const t = Math.min(1, (now - start) / duration)
      const eased = 1 - Math.pow(1 - t, 3)
      const value = from + (to - from) * eased
      animatedTotalRef.current = value
      setAnimatedTotal(value)
      if (t < 1) raf = requestAnimationFrame(tick)
    }

    raf = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(raf)
  }, [campaignTotal, thresholdAmount])

  useEffect(() => {
    setInvoiceForm((current) => ({
      ...formFromUser(user),
      entrepreneur_name: current.entrepreneur_name,
      entrepreneur_province: current.entrepreneur_province,
      entrepreneur_reason: current.entrepreneur_reason,
      rawInput: current.rawInput,
      invoice_number: current.invoice_number,
      purchase_amount: current.purchase_amount,
      issued_at: current.issued_at,
      issuer_name: current.issuer_name,
      cufe_tail: current.cufe_tail,
    }))
    setProfileForm({
      entrepreneur_name: user.entrepreneur_name ?? '',
      entrepreneur_province: user.entrepreneur_province ?? '',
      entrepreneur_reason: user.entrepreneur_reason ?? '',
    })
  }, [user])

  useEffect(() => {
    if (!scannerOn || entryMode !== 'scan') return undefined

    let scanner: Html5Qrcode | null = null
    let stopped = false

    async function start() {
      try {
        scanner = createInvoiceScanner()
        await scanner.start({ facingMode: 'environment' }, INVOICE_SCANNER_START_CONFIG, async (decodedText) => {
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

  useEffect(() => {
    if (!campaign?.slug) return undefined

    let cancelled = false
    void api
      .get<{ data: { campaign_total?: number } }>(`/campaigns/${campaign.slug}/progress`)
      .then((response) => {
        if (!cancelled) setCampaignTotal(Number(response.data.data.campaign_total ?? 0))
      })
      .catch(() => {
        if (!cancelled) setCampaignTotal(0)
      })

    return () => {
      cancelled = true
    }
  }, [campaign?.slug, user.id])

  async function saveDreamProfile(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSavingProfile(true)
    setSubmitError(null)
    setSubmitSuccess(null)

    try {
      const response = await api.post<{ data: User; message?: string }>('/auth/dream-profile', profileForm)
      onUserChange(response.data.data)
      setSubmitSuccess(response.data.message ?? 'Formulario guardado.')
    } catch (error) {
      setSubmitError(normalizeError(error))
    } finally {
      setSavingProfile(false)
    }
  }

  async function resolveInvoice(rawText: string) {
    try {
      setResolvingInvoice(true)
      const response = await api.post<{ data: ResolvedInvoiceData & { is_valid?: boolean; minimum_amount?: number } }>('/invoices/resolve', {
        qr_raw_text: rawText,
      })

      setScannerError(null)

      if (!response.data.data.is_valid) {
        setInvoiceValidated(false)
        setSubmitSuccess(null)
        setSubmitError(null)
        setScannerError(`La factura no supera el monto minimo de $${(response.data.data.minimum_amount ?? 25).toFixed(2)}.`)
        return
      }

      setInvoiceValidated(true)
      setSubmitSuccess(null)
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

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSubmitting(true)
    setSubmitError(null)
    setSubmitSuccess(null)

    try {
      const rawText = invoiceForm.rawInput || `${CUFE_SHORT_PREFIX}${invoiceForm.cufe_tail}`
      let invoicePayload = invoiceForm

      if (!invoiceValidated) {
        if (entryMode !== 'manual' || invoiceForm.cufe_tail.trim().replace(/\D/g, '').length < 10) {
          setSubmitError('Escanea la factura o escribe el CUFE antes de registrar.')
          return
        }

        setResolvingInvoice(true)
        try {
          const resolved = await api.post<{ data: ResolvedInvoiceData & { is_valid?: boolean; minimum_amount?: number } }>('/invoices/resolve', {
            qr_raw_text: rawText,
          })

          if (!resolved.data.data.is_valid) {
            setScannerError(`La factura no supera el monto minimo de $${(resolved.data.data.minimum_amount ?? 25).toFixed(2)}.`)
            return
          }

          invoicePayload = {
            ...invoicePayload,
            rawInput: rawText,
            cufe_tail: rawText.slice(-60),
            invoice_number: resolved.data.data.invoice_number ?? invoicePayload.invoice_number,
            purchase_amount: resolved.data.data.purchase_amount ?? invoicePayload.purchase_amount,
            issued_at: resolved.data.data.issued_at ?? invoicePayload.issued_at,
            issuer_name: resolved.data.data.issuer_name ?? invoicePayload.issuer_name,
          }
          setInvoiceValidated(true)
          setInvoiceForm(invoicePayload)
        } finally {
          setResolvingInvoice(false)
        }
      }

      const response = await api.post<{ invoice: RegisteredInvoice; message?: string; campaign_total?: number; campaign_threshold?: number; campaign_qualified?: boolean }>('/invoices/scan', {
        qr_raw_text: rawText,
        campaign_slug: campaign?.slug ?? null,
        purchase_amount: Number(invoicePayload.purchase_amount || 0),
        invoice_number: invoicePayload.invoice_number || null,
        issued_at: invoicePayload.issued_at || null,
        document_type: user.document_type,
        document_number: user.cedula,
        first_name: firstNameFromUser(user),
        last_name: lastNameFromUser(user),
        full_name: user.full_name,
        cedula: user.cedula,
        phone: user.phone || null,
        email: user.email || null,
        entrepreneur_name: user.entrepreneur_name || profileForm.entrepreneur_name || null,
        entrepreneur_province: user.entrepreneur_province || profileForm.entrepreneur_province || null,
        entrepreneur_reason: user.entrepreneur_reason || profileForm.entrepreneur_reason || null,
      })

      if (typeof response.data.campaign_total === 'number') {
        setCampaignTotal(response.data.campaign_total)
      }

      setSubmitSuccess(response.data.message ?? (response.data.campaign_qualified ? 'Participacion activa.' : 'Factura registrada.'))
      setInvoiceValidated(false)
      setScannerError(null)
      setInvoiceForm((current) => ({
        ...current,
        rawInput: '',
        invoice_number: '',
        purchase_amount: '',
        issued_at: '',
        issuer_name: '',
        cufe_tail: '',
      }))
    } catch (error) {
      setSubmitError(normalizeError(error))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="dream-page-compact">
      <div className="dream-split">
        <aside className="dream-showcase">
          <button className="dream-back" type="button" onClick={onBack}>← Volver</button>
          <img src="/logo_web.jpg" alt="Super Carnes" />
          <h1>Del Sueño al Puesto</h1>
          <span className="dream-showcase-user">{user.full_name} · {user.cedula}</span>

          <div className={`dream-tracker-big${campaignQualified ? ' is-complete' : ''}`}>
            <div className="dream-tracker-ring">
              <div className="dream-liquid-gauge">
                <div className="dream-liquid-fill" style={{ height: `${Math.max(trackerProgress, 6)}%` }}>
                  <span className="dream-liquid-surface" />
                </div>
                <span className="dream-liquid-bubble b1" />
                <span className="dream-liquid-bubble b2" />
                <span className="dream-liquid-bubble b3" />
              </div>
              <div className="dream-tracker-ring-label">
                <strong>${Math.round(animatedTotal)}</strong>
                <span>de ${thresholdAmount.toFixed(0)}</span>
              </div>
            </div>
            <p className={campaignQualified ? 'dream-tracker-done' : ''}>
              {campaignQualified ? '¡Participación activa!' : `Faltan $${remainingAmount.toFixed(2)}`}
            </p>
            <div className="dream-tracker-bar">
              <div className="dream-tracker-fill" style={{ width: `${trackerProgress}%` }} />
            </div>
          </div>
        </aside>

        <main className="dream-action">
          <div className="dream-steps">
            <span className={`dream-step ${!profileCompleted ? 'is-active' : 'is-done'}`}>
              <b>1</b> Formulario
            </span>
            <span className={`dream-step ${profileCompleted ? 'is-active' : ''}`}>
              <b>2</b> Registrar facturas
            </span>
          </div>

          <div className="dream-workspace-compact">
        {!profileCompleted ? (
          <form className="dream-panel dream-form" onSubmit={saveDreamProfile}>
            <h2>Cuéntanos por qué tu emprendimiento debe ganar una tolda</h2>
            <p className="promo-help">Completa esto primero: es lo único que falta para poder registrar tus facturas.</p>
            <label>
              Nombre del emprendimiento
              <input value={profileForm.entrepreneur_name} onChange={(event) => setProfileForm((current) => ({ ...current, entrepreneur_name: event.target.value }))} required />
            </label>
            <label>
              Ubicacion del emprendimiento
              <select value={profileForm.entrepreneur_province} onChange={(event) => setProfileForm((current) => ({ ...current, entrepreneur_province: event.target.value }))} required>
                <option value="">Selecciona provincia</option>
                {PANAMA_PROVINCES.map((province) => <option key={province} value={province}>{province}</option>)}
              </select>
            </label>
            <label>
              Por que debe ganar una tolda
              <textarea value={profileForm.entrepreneur_reason} onChange={(event) => setProfileForm((current) => ({ ...current, entrepreneur_reason: event.target.value }))} rows={3} required />
            </label>
            {submitError ? <div className="promo-alert">{submitError}</div> : null}
            <button className="dream-primary" type="submit" disabled={savingProfile}>{savingProfile ? 'Guardando...' : 'Guardar y continuar'}</button>
          </form>
        ) : (
          <form className="promo-panel dream-scan-panel" onSubmit={handleSubmit}>
            <p className="dream-confirm-line">✓ {user.entrepreneur_name} · {user.entrepreneur_province}</p>
            <div className="dream-panel-head">
              <span className="dream-panel-kicker">{entryMode === 'manual' ? 'Registro manual' : 'Escaneo de QR'}</span>
              <h2>{entryMode === 'manual' ? 'Ingresa el CUFE' : 'Registra tu factura'}</h2>
            </div>
            <div className="promo-mode-switch">
              <button className={entryMode === 'manual' ? 'is-active' : ''} type="button" onClick={() => setEntryMode('manual')}>Manual</button>
              <button className={entryMode === 'scan' ? 'is-active' : ''} type="button" onClick={() => setEntryMode('scan')}>Escanear QR</button>
              <a className="promo-mode-whatsapp" href="https://wa.me/50766153518?text=Hola%20Super%20Carnes,%20quiero%20registrar%20mi%20factura%20para%20Del%20Sue%C3%B1o%20al%20Puesto" target="_blank" rel="noreferrer">WhatsApp Del Sueño al Puesto</a>
            </div>

            {entryMode === 'scan' ? (
              <div className="promo-scan">
                {!scannerOn ? (
                  <button className="promo-primary" type="button" onClick={() => setScannerOn(true)}>Activar escaneo de QR</button>
                ) : (
                  <div id={QR_READER_ELEMENT_ID} className="promo-scanner" />
                )}
              </div>
            ) : (
              <>
                <div className="dream-info-block">
                  <p className="dream-info-block-title">
                    <span className="material-symbols-outlined" aria-hidden="true">info</span>
                    ¿Qué número debo escribir?
                  </p>
                  <p className="dream-info-block-copy">El CUFE de tu factura Super Carnes tiene varias partes separadas por guiones. Solo necesitas escribir el bloque final, los números después del último guión.</p>
                  <div className="dream-code-sample">
                    <span className="dream-code-sample-prefix">{CUFE_SHORT_PREFIX}</span>
                    <span className="dream-code-sample-tail">{CUFE_TAIL_EXAMPLE}</span>
                  </div>
                  <p className="dream-info-block-copy is-muted">Escribe solo la parte resaltada. La encontrarás al final de la factura, debajo del código QR, etiquetada como CUFE.</p>
                </div>

                <div className="promo-manual dream-cufe-entry">
                  <label>
                    <span>Código final del CUFE</span>
                    <div className="dream-cufe-input-group">
                      <span className="dream-cufe-prefix">{CUFE_SHORT_PREFIX_ABBR}</span>
                      <input
                        value={invoiceForm.cufe_tail}
                        onChange={(event) => {
                          setManualTouched(true)
                          setInvoiceValidated(false)
                          setInvoiceForm((current) => ({ ...current, cufe_tail: event.target.value.replace(/\D/g, '').slice(0, 60), rawInput: '' }))
                        }}
                        maxLength={60}
                        inputMode="numeric"
                        placeholder={CUFE_TAIL_EXAMPLE.slice(0, 18) + '...'}
                      />
                    </div>
                  </label>
                  <p className="promo-help">Solo los números finales, sin espacios ni guiones.</p>
                </div>
              </>
            )}

            {manualTouched && entryMode === 'manual' && !invoiceValidated && !scannerError ? <p className="promo-help">Listo. Ahora presiona registrar factura para validar y acumular.</p> : null}
            {scannerError ? <div className="promo-alert">{scannerError}</div> : null}
            {invoiceValidated ? (
              <div className="dream-ticket">
                <strong>Factura lista</strong>
                <span>{invoiceForm.invoice_number || 'Sin numero'} · ${Number(invoiceForm.purchase_amount || 0).toFixed(2)}</span>
              </div>
            ) : null}
            {submitError ? <div className="promo-alert">{submitError}</div> : null}
            {submitSuccess ? <div className="promo-success-msg">{submitSuccess}</div> : null}
            <button className="promo-primary" type="submit" disabled={!canSubmitInvoice}>
              {submitting || resolvingInvoice ? 'Validando y registrando...' : 'Registrar factura y acumular'}
            </button>
          </form>
        )}
          </div>
        </main>
      </div>
    </div>
  )
}

export function LegacyThresholdPromoLanding({
  campaign,
  onBack,
}: {
  campaign: Campaign | null
  onBack: () => void
}) {
  const [entryMode, setEntryMode] = useState<EntryMode>('scan')
  const [invoiceForm, setInvoiceForm] = useState<InvoiceFormState>(emptyForm())
  const [scannerOn, setScannerOn] = useState(false)
  const [scannerError, setScannerError] = useState<string | null>(null)
  const [resolvingInvoice, setResolvingInvoice] = useState(false)
  const [submitError, setSubmitError] = useState<string | null>(null)
  const [submitSuccess, setSubmitSuccess] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [invoiceValidated, setInvoiceValidated] = useState(false)
  const [manualTouched, setManualTouched] = useState(false)
  const [campaignTotal, setCampaignTotal] = useState(0)
  const [branchOptions, setBranchOptions] = useState<BranchOption[]>([])
  const [branchError, setBranchError] = useState<string | null>(null)

  const thresholdAmount = Number(campaign?.entry_threshold_amount ?? 300)
  const campaignQualified = campaignTotal >= thresholdAmount
  const remainingAmount = Math.max(thresholdAmount - campaignTotal, 0)

  const steps = useMemo(
    () => [
      { id: 1, title: 'Llena tu perfil' },
      { id: 2, title: 'Suma facturas' },
      { id: 3, title: 'Activa tu participación' },
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
        await scanner.start({ facingMode: 'environment' }, INVOICE_SCANNER_START_CONFIG, async (decodedText) => {
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

  useEffect(() => {
    let cancelled = false

    async function loadBranches() {
      try {
        const response = await api.get<{ data: BranchOption[] }>('/public/branches')
        if (!cancelled) {
          setBranchOptions(response.data.data ?? [])
          setBranchError(null)
        }
      } catch (error) {
        if (!cancelled) {
          setBranchError(normalizeError(error))
        }
      }
    }

    void loadBranches()

    return () => {
      cancelled = true
    }
  }, [])

  useEffect(() => {
    if (campaign?.participation_mode !== 'threshold_form') return undefined

    const documentNumber = invoiceForm.document_number.trim()
    if (!documentNumber) {
      setCampaignTotal(0)
      return undefined
    }

    let cancelled = false
    const timer = window.setTimeout(() => {
      void api
        .get<{ data: { campaign_total?: number; campaign_threshold?: number; campaign_qualified?: boolean } }>(`/campaigns/${campaign?.slug}/progress`, {
          params: { document_number: documentNumber },
        })
        .then((response) => {
          if (!cancelled) {
            setCampaignTotal(Number(response.data.data.campaign_total ?? 0))
          }
        })
        .catch(() => {
          if (!cancelled) {
            setCampaignTotal(0)
          }
        })
    }, 450)

    return () => {
      cancelled = true
      window.clearTimeout(timer)
    }
  }, [campaign?.slug, campaign?.participation_mode, invoiceForm.document_number])

  async function resolveInvoice(rawText: string) {
    try {
      setResolvingInvoice(true)
      const response = await api.post<{ data: ResolvedInvoiceData & { is_valid?: boolean; minimum_amount?: number } }>('/invoices/resolve', {
        qr_raw_text: rawText,
      })

      setScannerError(null)

      if (!response.data.data.is_valid) {
        setInvoiceValidated(false)
        setSubmitSuccess(null)
        setSubmitError(null)
        setScannerError(`La factura no supera el monto minimo de $${(response.data.data.minimum_amount ?? 25).toFixed(2)}.`)
        return
      }

      setInvoiceValidated(true)
      setSubmitSuccess(null)
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
    await resolveInvoice(`${CUFE_SHORT_PREFIX}${rawTail}`)
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSubmitting(true)
    setSubmitError(null)
    setSubmitSuccess(null)

    try {
      const rawText = invoiceForm.rawInput || `${CUFE_SHORT_PREFIX}${invoiceForm.cufe_tail}`
      const fullName = `${invoiceForm.first_name.trim()} ${invoiceForm.last_name.trim()}`.trim()
      const response = await api.post<{ invoice: RegisteredInvoice; message?: string; campaign_total?: number; campaign_threshold?: number; campaign_qualified?: boolean }>('/invoices/scan', {
        qr_raw_text: rawText,
        campaign_slug: campaign?.slug ?? null,
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
        nearest_branch_id: invoiceForm.nearest_branch_id ? Number(invoiceForm.nearest_branch_id) : null,
        entrepreneur_name: invoiceForm.entrepreneur_name || null,
        entrepreneur_province: invoiceForm.entrepreneur_province || null,
        entrepreneur_type: invoiceForm.entrepreneur_type || null,
        entrepreneur_story: invoiceForm.entrepreneur_story || null,
        entrepreneur_reason: invoiceForm.entrepreneur_reason || null,
      })

      if (typeof response.data.campaign_total === 'number') {
        setCampaignTotal(response.data.campaign_total)
      }

      setSubmitSuccess(response.data.message ?? (response.data.campaign_qualified ? 'Participación activa.' : 'Factura registrada.'))
      setInvoiceValidated(false)
      setScannerError(null)
      setInvoiceForm((current) => ({
        ...current,
        rawInput: '',
        invoice_number: '',
        purchase_amount: '',
        issued_at: '',
        issuer_name: '',
        cufe_tail: '',
      }))
    } catch (error) {
      setSubmitError(normalizeError(error))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="promo-shell">
      <div className="promo-ambient" />
      <main className="promo-layout promo-layout-threshold">
        <section className="promo-hero">
          <div className="promo-hero-copy">
            <button className="promo-back" type="button" onClick={onBack}>
              Volver a promociones
            </button>
            <p className="promo-kicker">/{campaign?.slug ?? 'promo'}</p>
            {campaignQualified ? <p className="promo-valid-badge">Participación activa</p> : null}
            <h1>
              {campaign?.name ?? 'Promocion'}
              <span>Super Carnes</span>
            </h1>
            <p>{campaign?.description ?? 'Completa tus datos y acumula facturas hasta activar tu participación.'}</p>
            <div className="promo-tracker">
              <div className="promo-tracker-head">
                <strong>Acumulado</strong>
                <span>${Math.min(campaignTotal, thresholdAmount).toFixed(2)} / {thresholdAmount.toFixed(2)}</span>
              </div>
              <div className="promo-tracker-bar">
                <i style={{ width: `${Math.min(100, (campaignTotal / thresholdAmount) * 100)}%` }} />
              </div>
              <p>{campaignQualified ? 'Ya alcanzaste el monto requerido. Tu participación está activa.' : `Te faltan $${remainingAmount.toFixed(2)} para activar tu participación.`}</p>
            </div>
            <div className="promo-stepper">
              {steps.map((step) => (
                <div key={step.id} className={`promo-step ${campaignQualified || step.id <= (invoiceValidated ? 2 : 1) ? 'is-active' : ''}`}>
                  <span>{step.id}</span>
                  <strong>{step.title}</strong>
                </div>
              ))}
            </div>
          </div>

          <div className="promo-art">
            <div className="promo-paper">
              <span>Umbral $300</span>
              <div className="promo-paper-lines">
                <i />
                <i />
                <i />
                <i />
              </div>
            </div>
            <div className="promo-dad">
              <img src={campaign?.hero_image_url || '/gaby-torres-celebration.webp'} alt={campaign?.name ?? 'Promocion'} />
            </div>
            <div className="promo-ball" aria-hidden="true">
              <img src="/trionda-ball.svg" alt="" />
            </div>
          </div>
        </section>

        <aside className="promo-panel">
          <div className="promo-panel-card promo-panel-highlight">
            <p className="promo-panel-label">Promo de emprendimiento</p>
            <h2>{campaignQualified ? 'Tu participación está habilitada' : 'Acumula facturas hasta llegar al monto requerido'}</h2>
            <p>{campaignQualified ? 'Ya puedes seguir enviando facturas y el equipo puede revisar tus historias.' : 'El formulario está disponible desde el inicio. Sigue cargando facturas hasta completar el umbral.'}</p>
          </div>

          <form className="promo-panel-card promo-form promo-form-compact" onSubmit={handleSubmit}>
            <div className="promo-form-head">
              <p>Registro inicial</p>
              <h3>Completa tu perfil de emprendimiento</h3>
            </div>

            <div className="promo-rule">
              <strong>Cómo funciona</strong>
              <div className="promo-last">
                <span>1. Llena tus datos y cuenta tu historia.</span>
                <span>2. Registra facturas por CUFE hasta acumular ${thresholdAmount.toFixed(2)}.</span>
                <span>3. Al llegar al monto, tu participación queda activa.</span>
              </div>
            </div>

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

            {invoiceValidated ? (
              <div className="promo-rule">
                <strong>Factura cargada</strong>
                <div className="promo-last">
                  <span>Factura: {invoiceForm.invoice_number || '—'}</span>
                  <span>Monto: ${Number(invoiceForm.purchase_amount || 0).toFixed(2)}</span>
                  <span>Fecha: {invoiceForm.issued_at || '—'}</span>
                </div>
              </div>
            ) : null}

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
                <input value={invoiceForm.document_number} onChange={(e) => setInvoiceForm((current) => ({ ...current, document_number: sanitizeDocumentNumber(current.document_type, e.target.value) }))} placeholder={documentPlaceholder(invoiceForm.document_type)} required />
              </label>
              <label>
                Nombre
                <input value={invoiceForm.first_name} onChange={(e) => setInvoiceForm((current) => ({ ...current, first_name: sanitizeName(e.target.value) }))} placeholder="Nombre(s)" required />
              </label>
              <label>
                Apellidos
                <input value={invoiceForm.last_name} onChange={(e) => setInvoiceForm((current) => ({ ...current, last_name: sanitizeName(e.target.value) }))} placeholder="Apellido(s)" required />
              </label>
              <label>
                Teléfono
                <input value={invoiceForm.phone} onChange={(e) => setInvoiceForm((current) => ({ ...current, phone: sanitizePanamaPhone(e.target.value) }))} placeholder="6XXX-XXXX" inputMode="tel" required />
              </label>
              <label>
                Correo
                <input value={invoiceForm.email} onChange={(e) => setInvoiceForm((current) => ({ ...current, email: e.target.value.trim() }))} type="email" placeholder="correo@dominio.com" required />
              </label>
              <label>
                Nombre del emprendimiento
                <input value={invoiceForm.entrepreneur_name} onChange={(e) => setInvoiceForm((current) => ({ ...current, entrepreneur_name: e.target.value }))} placeholder="Nombre del emprendimiento" required />
              </label>
              <label>
                Provincia del emprendimiento
                <input value={invoiceForm.entrepreneur_province} onChange={(e) => setInvoiceForm((current) => ({ ...current, entrepreneur_province: e.target.value }))} placeholder="Provincia" required />
              </label>
              <label>
                Sucursal de Super Carnes más cercana
                <select value={invoiceForm.nearest_branch_id} onChange={(e) => setInvoiceForm((current) => ({ ...current, nearest_branch_id: e.target.value }))} required>
                  <option value="">Selecciona una sucursal</option>
                  {branchOptions.map((branch) => (
                    <option key={branch.id} value={branch.id}>
                      {branch.name} {branch.code ? `(${branch.code})` : ''}
                    </option>
                  ))}
                </select>
                {branchError ? <small className="promo-help">{branchError}</small> : null}
              </label>
              <label>
                Tipo de emprendimiento
                <input value={invoiceForm.entrepreneur_type} onChange={(e) => setInvoiceForm((current) => ({ ...current, entrepreneur_type: e.target.value }))} placeholder="Comida, artesanías, belleza..." required />
              </label>
              <label className="promo-form-wide">
                Historia del emprendimiento
                <textarea value={invoiceForm.entrepreneur_story} onChange={(e) => setInvoiceForm((current) => ({ ...current, entrepreneur_story: e.target.value }))} rows={4} required />
              </label>
              <label className="promo-form-wide">
                ¿Por qué deben ganar la tolda?
                <textarea value={invoiceForm.entrepreneur_reason} onChange={(e) => setInvoiceForm((current) => ({ ...current, entrepreneur_reason: e.target.value }))} rows={4} required />
              </label>
              <label className="promo-form-wide">
                Ultimos 60 numeros del CUFE
                <input value={invoiceForm.cufe_tail} onChange={(e) => setInvoiceForm((current) => ({ ...current, cufe_tail: e.target.value.replace(/\D/g, '').slice(0, 60) }))} maxLength={60} inputMode="numeric" placeholder="Escribe solo los ultimos 60 numeros" required />
              </label>
            </div>

            {submitError ? <div className="promo-alert">{submitError}</div> : null}
            {submitSuccess ? <div className="promo-success-msg">{submitSuccess}</div> : null}
            <button className="promo-primary" type="submit" disabled={submitting || !invoiceValidated}>
              {submitting ? 'Enviando...' : invoiceValidated ? 'Registrar factura y seguir acumulando' : 'Valida la factura para continuar'}
            </button>
          </form>
        </aside>
      </main>
    </div>
  )
}

function PromoNotFound({ onBack }: { onBack: () => void }) {
  return (
    <div className="promo-shell promo-shell-catalog">
      <div className="promo-ambient" />
      <main className="promo-catalog-layout">
        <div className="promo-state">
          <h1>Promocion no encontrada</h1>
          <p>Puede estar desactivada o el slug no existe.</p>
          <button className="promo-primary" type="button" onClick={onBack}>
            Volver a promociones
          </button>
        </div>
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

function sanitizeName(value: string) {
  return value.replace(/[^A-Za-zÁÉÍÓÚÑáéíóúñÜü\s'.-]/g, '')
}

function sanitizeDocumentNumber(type: InvoiceFormState['document_type'], value: string) {
  if (type === 'passport' || type === 'residente') {
    return value.toUpperCase().replace(/[^A-Z0-9-]/g, '')
  }
  return value.replace(/[^0-9-]/g, '')
}

function sanitizePanamaPhone(value: string) {
  return value.replace(/[^0-9-]/g, '').slice(0, 9)
}

function normalizeError(error: unknown) {
  if (typeof error === 'object' && error && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }).response
    const message = response?.data?.message
    if (message) return message
    const errors = response?.data?.errors
    if (errors) {
      const firstKey = Object.keys(errors)[0]
      return firstKey ? errors[firstKey]?.[0] ?? 'Ocurrio un error inesperado.' : 'Ocurrio un error inesperado.'
    }
  }
  if (error instanceof Error) return error.message
  return 'Ocurrio un error inesperado.'
}
