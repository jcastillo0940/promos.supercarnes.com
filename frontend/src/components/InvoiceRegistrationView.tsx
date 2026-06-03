import type { ChangeEvent, FormEvent } from 'react'
import { useEffect, useMemo, useRef, useState } from 'react'
import type { RegisteredInvoice, ResolvedInvoiceData } from '../types'

const CUFE_PREFIX = 'FE01200000032812-2-249262-'

type InvoiceEntryMode = 'scan' | 'manual'

interface InvoiceFormState {
  rawInput: string
  invoice_number: string
  purchase_amount: string
  issued_at: string
}

interface InvoiceScannerDebugInfo {
  origin: string
  protocol: string
  hostname: string
  isSecureContext: boolean
  hasMediaDevices: boolean
  hasGetUserMedia: boolean
  cameraPermission: string
  fileReaderSupported: boolean
  userAgent: string
  likelyCameraBlockedBySecurity: boolean
  lastStage: string
  lastError: string | null
  barcodeDetectorAvailable: boolean
  scannerType: 'native' | 'html5-qrcode' | 'none'
  activeFormats: string[]
  cameraResolution: string
}

interface InvoiceRegistrationViewProps {
  invoices: RegisteredInvoice[]
  invoiceEntryMode: InvoiceEntryMode
  invoiceForm: InvoiceFormState
  invoiceGalleryProcessing: boolean
  invoiceScannerActivated: boolean
  invoiceScannerError: string | null
  invoiceScannerDebug: InvoiceScannerDebugInfo
  invoiceSubmitting: boolean
  resolvedInvoiceData: ResolvedInvoiceData | null
  onActivateScan: () => void
  onRegister: (rawCufe: string) => void
  onReset: () => void
  onSubmit: (event: FormEvent<HTMLFormElement>) => void | Promise<void>
  onGalleryUpload: (event: ChangeEvent<HTMLInputElement>) => void | Promise<void>
  onModeChange: (mode: InvoiceEntryMode) => void
  onFieldChange: <K extends keyof InvoiceFormState>(field: K, value: InvoiceFormState[K]) => void
  showScannerDebug?: boolean
}

function formatCurrency(value: number | string | null | undefined) {
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat('es-PA', { style: 'currency', currency: 'USD' }).format(
    Number.isFinite(amount) ? amount : 0,
  )
}

function formatDate(dateValue: string) {
  if (!dateValue) return ''
  const date = new Date(`${dateValue}T12:00:00`)
  if (Number.isNaN(date.getTime())) return dateValue
  return date.toLocaleDateString('es-PA', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'America/Panama' })
}

function DebugRow({ label, value, ok }: { label: string; value: string; ok: boolean }) {
  return (
    <div className="flex gap-2 items-start">
      <span className={`flex-shrink-0 w-2 h-2 rounded-full mt-0.5 ${ok ? 'bg-emerald-500' : 'bg-red-500'}`} />
      <span className="text-on-surface-variant/70 flex-shrink-0 min-w-[110px]">{label}:</span>
      <span className={ok ? 'text-on-surface' : 'text-red-400'}>{value}</span>
    </div>
  )
}

function invoiceStatusMeta(status: string) {
  if (status === 'approved') return { tone: 'approved' as const, label: 'GOL VÁLIDO', icon: 'verified', points: true }
  if (status === 'pending') return { tone: 'pending' as const, label: 'EN REVISIÓN', icon: 'update', points: false }
  return { tone: 'rejected' as const, label: 'RECHAZADA', icon: 'dangerous', points: false }
}

export function InvoiceRegistrationView({
  invoices,
  invoiceEntryMode,
  invoiceForm,
  invoiceScannerActivated,
  invoiceScannerError,
  invoiceScannerDebug,
  invoiceSubmitting,
  resolvedInvoiceData,
  onActivateScan,
  onRegister,
  onReset,
  onModeChange,
  onFieldChange,
  showScannerDebug = false,
}: InvoiceRegistrationViewProps) {
  const hasResolved = Boolean(resolvedInvoiceData)
  const [debugOpen, setDebugOpen] = useState(false)
  const loaderRef = useRef<HTMLDivElement | null>(null)

  useEffect(() => {
    if (invoiceSubmitting && loaderRef.current) {
      loaderRef.current.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
  }, [invoiceSubmitting])

  const deriveShortCode = (raw: string) => {
    if (!raw) return ''
    if (raw.startsWith(CUFE_PREFIX)) return raw.slice(CUFE_PREFIX.length)
    // URL DGI: ?chFE=FE01200000032812-2-249262-SHORTCODE&...
    const chFeMatch = raw.match(/[?&]chFE=[^&]*-([A-Z0-9]{10,})(?:&|$)/i)
    if (chFeMatch?.[1]) return chFeMatch[1]
    const idx = raw.lastIndexOf('-')
    return idx >= 0 ? raw.slice(idx + 1) : raw
  }

  const [shortCode, setShortCode] = useState(() => deriveShortCode(invoiceForm.rawInput))

  useEffect(() => {
    setShortCode(deriveShortCode(invoiceForm.rawInput))
  }, [invoiceForm.rawInput])

  function handleShortCodeChange(value: string, autoPaste = false) {
    const trimmed = value.replace(/\s/g, '')
    setShortCode(trimmed)
    onFieldChange('rawInput', trimmed ? CUFE_PREFIX + trimmed : '')
    // Auto-registrar si se pegó un código con longitud suficiente (>= 30 chars)
    if (autoPaste && trimmed.length >= 30) {
      setTimeout(() => onRegister(CUFE_PREFIX + trimmed), 0)
    }
  }

  const totalPoints = useMemo(
    () =>
      invoices
        .filter((i) => i.validation_status === 'approved')
        .reduce((sum, i) => sum + Number(i.points_awarded ?? 0), 0),
    [invoices],
  )

  const invoiceCards = useMemo(
    () =>
      invoices.slice(0, 5).map((invoice) => {
        const meta = invoiceStatusMeta(invoice.validation_status)
        const colorMap = {
          approved: { border: 'border-emerald-500', bg: 'bg-emerald-500/10', text: 'text-emerald-400', badge: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' },
          pending:  { border: 'border-yellow-500',  bg: 'bg-yellow-500/10',  text: 'text-yellow-400',  badge: 'bg-yellow-500/20  text-yellow-400  border-yellow-500/30'  },
          rejected: { border: 'border-red-500',     bg: 'bg-red-500/10',     text: 'text-red-400',     badge: 'bg-red-500/20     text-red-400     border-red-500/30'     },
        }
        const c = colorMap[meta.tone]
        const title = invoice.invoice_number ? `#${invoice.invoice_number}` : `#${String(invoice.id).padStart(6, '0')}`
        const date = invoice.issued_at ?? invoice.created_at

        return (
          <div key={invoice.id} className={`flex items-center gap-3 p-3 rounded-xl border-l-4 ${c.border} bg-surface-container-lowest`}>
            <div className={`w-9 h-9 rounded-full ${c.bg} flex items-center justify-center flex-shrink-0`}>
              <span className={`material-symbols-outlined text-base ${c.text}`} data-weight="fill">{meta.icon}</span>
            </div>
            <div className="flex-1 min-w-0">
              <div className="font-title-md text-on-surface text-sm truncate">{title}</div>
              <div className="text-xs text-on-surface-variant">{date ? formatDate(date) : ''} · {formatCurrency(invoice.purchase_amount)}</div>
            </div>
            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full border ${c.badge} whitespace-nowrap`}>
              {meta.points ? `+${Number(invoice.points_awarded ?? 0).toFixed(1)} GOL` : meta.label}
            </span>
          </div>
        )
      }),
    [invoices],
  )

  return (
    <div className="facturas-view flex flex-col gap-6 max-w-lg mx-auto lg:max-w-none">

      {/* Tabs: QR primero, Manual segundo */}
      <div className="flex rounded-2xl border border-outline-variant overflow-hidden">
        <button
          type="button"
          className={`flex-1 flex items-center justify-center gap-2 py-3.5 text-sm font-semibold transition-colors ${
            invoiceEntryMode === 'scan'
              ? 'bg-primary-container text-white'
              : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container'
          }`}
          onClick={() => onModeChange('scan')}
        >
          <span className="material-symbols-outlined text-lg">qr_code_scanner</span>
          Escanear QR
        </button>
        <button
          type="button"
          className={`flex-1 flex items-center justify-center gap-2 py-3.5 text-sm font-semibold transition-colors border-l border-outline-variant ${
            invoiceEntryMode === 'manual'
              ? 'bg-primary-container text-white'
              : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container'
          }`}
          onClick={() => onModeChange('manual')}
        >
          <span className="material-symbols-outlined text-lg">edit</span>
          Ingresar CUFE
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <section className="lg:col-span-5 flex flex-col gap-4">

          {/* Panel QR */}
          {invoiceEntryMode === 'scan' && !invoiceSubmitting && (
            <div className="bg-surface-container-low rounded-2xl border border-outline-variant overflow-hidden">
              {!invoiceScannerActivated ? (
                <div className="flex flex-col items-center gap-4 px-6 py-10">
                  <span className="material-symbols-outlined text-5xl text-primary-container">qr_code_scanner</span>
                  <p className="text-sm text-on-surface-variant text-center">
                    Apunta la cámara al código QR de tu factura DGI para registrarla automáticamente.
                  </p>
                  <button
                    type="button"
                    className="flex items-center gap-2 px-6 py-3 rounded-xl bg-primary-container text-white font-semibold text-sm transition-opacity hover:opacity-90"
                    onClick={onActivateScan}
                  >
                    <span className="material-symbols-outlined text-base">videocam</span>
                    Activar escaneo de QR
                  </button>
                </div>
              ) : (
                <>
                  <div className="px-5 pt-5 pb-3">
                    <p className="text-sm text-on-surface-variant text-center">
                      Apunta la cámara al código QR de tu factura DGI
                    </p>
                  </div>
                  <div id="dgi-qr-reader" className="w-full min-h-[280px] bg-black" />
              {invoiceScannerError && (
                <div className="mx-4 mb-4 mt-2 flex items-start gap-2 bg-red-500/10 border border-red-500/20 rounded-xl p-3 text-sm text-red-400">
                  <span className="material-symbols-outlined text-base flex-shrink-0 mt-0.5">error</span>
                  {invoiceScannerError}
                </div>
              )}
              <div className="px-5 pb-3 pt-2 text-center">
                <p className="text-xs text-on-surface-variant">
                  Si no funciona el escáner,{' '}
                  <button type="button" className="underline text-primary-container" onClick={() => onModeChange('manual')}>
                    ingresa el CUFE manualmente
                  </button>
                </p>
              </div>

              {/* Panel de debug técnico */}
              {showScannerDebug && <div className="border-t border-outline-variant">
                <button
                  type="button"
                  className="w-full flex items-center justify-between px-4 py-2.5 text-xs text-on-surface-variant hover:bg-surface-container transition-colors"
                  onClick={() => setDebugOpen((v) => !v)}
                >
                  <span className="flex items-center gap-1.5">
                    <span className="material-symbols-outlined text-sm">bug_report</span>
                    Info técnica del escáner
                  </span>
                  <span className="material-symbols-outlined text-sm">{debugOpen ? 'expand_less' : 'expand_more'}</span>
                </button>

                {debugOpen && (
                  <div className="px-4 pb-4 font-mono text-[11px] space-y-1">
                    <DebugRow
                      label="Escáner"
                      value={
                        invoiceScannerDebug.scannerType === 'native'
                          ? 'BarcodeDetector nativo ✓'
                          : invoiceScannerDebug.scannerType === 'html5-qrcode'
                            ? 'html5-qrcode (fallback)'
                            : 'sin iniciar'
                      }
                      ok={invoiceScannerDebug.scannerType !== 'none'}
                    />
                    <DebugRow
                      label="Etapa"
                      value={invoiceScannerDebug.lastStage}
                      ok={!['idle', 'camera-start-failed', 'media-devices-missing', 'insecure-context'].includes(invoiceScannerDebug.lastStage)}
                    />
                    {invoiceScannerDebug.lastError && (
                      <DebugRow label="Último error" value={invoiceScannerDebug.lastError} ok={false} />
                    )}
                    <DebugRow
                      label="BarcodeDetector"
                      value={invoiceScannerDebug.barcodeDetectorAvailable ? 'disponible' : 'no disponible'}
                      ok={invoiceScannerDebug.barcodeDetectorAvailable}
                    />
                    {invoiceScannerDebug.activeFormats.length > 0 && (
                      <DebugRow
                        label="Formatos activos"
                        value={invoiceScannerDebug.activeFormats.join(', ')}
                        ok={invoiceScannerDebug.activeFormats.includes('qr_code')}
                      />
                    )}
                    <DebugRow
                      label="Permiso cámara"
                      value={invoiceScannerDebug.cameraPermission}
                      ok={invoiceScannerDebug.cameraPermission === 'granted'}
                    />
                    <DebugRow
                      label="HTTPS / secure"
                      value={invoiceScannerDebug.isSecureContext ? 'sí' : 'NO — cámara bloqueada'}
                      ok={invoiceScannerDebug.isSecureContext}
                    />
                    <DebugRow
                      label="mediaDevices"
                      value={invoiceScannerDebug.hasMediaDevices ? 'disponible' : 'no disponible'}
                      ok={invoiceScannerDebug.hasMediaDevices}
                    />
                    <DebugRow
                      label="getUserMedia"
                      value={invoiceScannerDebug.hasGetUserMedia ? 'disponible' : 'no disponible'}
                      ok={invoiceScannerDebug.hasGetUserMedia}
                    />
                    {invoiceScannerDebug.cameraResolution && (
                      <DebugRow
                        label="Resolución cámara"
                        value={invoiceScannerDebug.cameraResolution}
                        ok={true}
                      />
                    )}
                    <DebugRow label="Origen" value={invoiceScannerDebug.origin} ok={true} />
                    <div className="pt-1 text-on-surface-variant/50 break-all leading-relaxed">
                      UA: {invoiceScannerDebug.userAgent}
                    </div>
                  </div>
                )}
              </div>}
                </>
              )}
            </div>
          )}

          {/* Panel Manual */}
          {invoiceEntryMode === 'manual' && !invoiceSubmitting && (
            <div className="bg-surface-container-low rounded-2xl border border-outline-variant p-5 flex flex-col gap-4">

              {/* Explicativo */}
              <div className="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 flex flex-col gap-2.5">
                <p className="text-sm font-semibold text-blue-300 flex items-center gap-2">
                  <span className="material-symbols-outlined text-base">info</span>
                  ¿Qué número debo escribir?
                </p>
                <p className="text-xs text-on-surface-variant leading-relaxed">
                  El CUFE de tu factura Super Carnes tiene varias partes separadas por guiones.
                  Solo necesitas escribir el bloque final, los números después del último guion:
                </p>
                <div className="font-mono text-[11px] bg-surface-container rounded-lg p-3 break-all leading-relaxed">
                  <span className="text-on-surface-variant/40">FE01200000032812-2-249262-</span><span className="text-emerald-400 font-bold">6300032026051830904933463090311813389704</span>
                </div>
                <p className="text-xs text-on-surface-variant/70">
                  Escribe solo la parte en <span className="text-emerald-400 font-semibold">verde</span>. Lo encontrarás al final de la factura, debajo del código QR, etiquetado como CUFE.
                </p>
              </div>

              {/* Input del código corto */}
              <div>
                <label className="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">
                  Código final del CUFE
                </label>
                <div className="flex flex-col gap-1.5">
                  <div className="flex items-center rounded-xl border border-outline-variant overflow-hidden bg-surface-container-lowest focus-within:border-primary-container transition-colors">
                    <span className="px-3 py-3 text-xs text-on-surface-variant/40 font-mono bg-surface-container border-r border-outline-variant select-none whitespace-nowrap shrink-0">
                      FE…-249262-
                    </span>
                    <input
                      type="text"
                      inputMode="numeric"
                      className="flex-1 min-w-0 bg-transparent px-3 py-3 text-on-surface text-sm font-mono focus:outline-none"
                      placeholder="6300032026051830…"
                      value={shortCode}
                      onChange={(e) => handleShortCodeChange(e.target.value)}
                      onPaste={(e) => {
                        const pasted = e.clipboardData.getData('text')
                        e.preventDefault()
                        handleShortCodeChange(pasted, true)
                      }}
                    />
                  </div>
                  <p className="text-xs text-on-surface-variant">
                    Solo los números finales, sin espacios ni guiones.
                  </p>
                </div>
              </div>

              {invoiceScannerError && (
                <div className="flex items-start gap-2 bg-red-500/10 border border-red-500/20 rounded-xl p-3 text-sm text-red-400">
                  <span className="material-symbols-outlined text-base flex-shrink-0 mt-0.5">error</span>
                  {invoiceScannerError}
                </div>
              )}

              <button
                type="button"
                disabled={shortCode.length < 10 || invoiceSubmitting}
                onClick={() => onRegister(CUFE_PREFIX + shortCode)}
                className="w-full py-3.5 bg-primary-container text-white font-bold text-sm rounded-xl flex items-center justify-center gap-2 hover:opacity-90 active:scale-[0.98] transition-all disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <span className="material-symbols-outlined text-base">sports_soccer</span>
                {invoiceSubmitting ? 'Registrando…' : 'Registrar factura'}
              </button>
            </div>
          )}

          {/* Loader mientras se registra */}
          {invoiceSubmitting && (
            <div ref={loaderRef} className="bg-surface-container-low rounded-2xl border border-outline-variant p-8 flex flex-col items-center gap-5 text-center">
              <div className="relative">
                <div className="w-16 h-16 rounded-full border-4 border-outline-variant border-t-primary-container animate-spin" />
                <span className="material-symbols-outlined absolute inset-0 flex items-center justify-center text-primary-container text-2xl" data-weight="fill">
                  receipt_long
                </span>
              </div>
              <div>
                <p className="font-bold text-on-surface text-lg">Verificando Factura…</p>
                <p className="text-sm text-on-surface-variant mt-1.5">
                  Estamos consultando tu factura en la DGI.<br />Por favor espera, puede tardar hasta 45 segundos.
                </p>
              </div>
              <div className="flex gap-1.5 mt-1">
                <span className="w-2 h-2 rounded-full bg-primary-container animate-bounce [animation-delay:0ms]" />
                <span className="w-2 h-2 rounded-full bg-primary-container animate-bounce [animation-delay:150ms]" />
                <span className="w-2 h-2 rounded-full bg-primary-container animate-bounce [animation-delay:300ms]" />
              </div>
            </div>
          )}

          {/* Resultado del registro */}
          {hasResolved && !invoiceSubmitting && (
            <div className="flex flex-col gap-3">
              <div className="bg-surface-container-low rounded-2xl border border-emerald-500/30 overflow-hidden">
                <div className="flex items-center gap-3 px-5 py-4 border-b border-outline-variant bg-emerald-500/10">
                  <div className="w-9 h-9 rounded-full bg-emerald-500/20 flex items-center justify-center flex-shrink-0">
                    <span className="material-symbols-outlined text-emerald-400 text-xl" data-weight="fill">check_circle</span>
                  </div>
                  <div>
                    <div className="font-bold text-emerald-400 text-sm">¡Factura registrada!</div>
                    {resolvedInvoiceData?.issuer_name && (
                      <div className="text-xs text-on-surface-variant">{resolvedInvoiceData.issuer_name}</div>
                    )}
                  </div>
                </div>
                <div className="grid grid-cols-3 divide-x divide-outline-variant">
                  <div className="px-4 py-3 text-center">
                    <div className="text-xs text-on-surface-variant mb-0.5">Monto</div>
                    <div className="font-semibold text-primary-container text-sm">{formatCurrency(resolvedInvoiceData?.purchase_amount)}</div>
                  </div>
                  <div className="px-4 py-3 text-center">
                    <div className="text-xs text-on-surface-variant mb-0.5">Fecha</div>
                    <div className="font-semibold text-on-surface text-sm">{formatDate(resolvedInvoiceData?.issued_at ?? '')}</div>
                  </div>
                  <div className="px-4 py-3 text-center">
                    <div className="text-xs text-on-surface-variant mb-0.5">CUFE</div>
                    <div className="font-semibold text-on-surface text-xs truncate">{resolvedInvoiceData?.cufe?.slice(-8)}</div>
                  </div>
                </div>
              </div>
              <button
                type="button"
                onClick={onReset}
                className="w-full py-3.5 bg-surface-container border border-outline-variant text-on-surface font-semibold text-sm rounded-2xl flex items-center justify-center gap-2 hover:bg-surface-container-high transition-colors"
              >
                <span className="material-symbols-outlined text-base">qr_code_scanner</span>
                Registrar otra factura
              </button>
            </div>
          )}

        </section>

        {/* Historial */}
        <section className="lg:col-span-7">
          <div className="bg-surface-container-low rounded-2xl border border-outline-variant flex flex-col overflow-hidden">
            <div className="flex items-center justify-between px-5 py-4 border-b border-outline-variant">
              <h2 className="font-semibold text-on-surface">Mis facturas</h2>
              {totalPoints > 0 && (
                <span className="text-xs font-bold px-3 py-1 rounded-full bg-primary-container/20 text-primary-container border border-primary-container/30">
                  {totalPoints.toFixed(1)} goles totales
                </span>
              )}
            </div>

            <div className="flex-1 overflow-y-auto p-4 space-y-2.5 max-h-[520px]">
              {invoiceCards.length > 0 ? invoiceCards : (
                <div className="flex flex-col items-center justify-center py-12 text-center gap-3">
                  <span className="material-symbols-outlined text-4xl text-on-surface-variant/40">receipt_long</span>
                  <div>
                    <p className="text-sm font-medium text-on-surface-variant">Sin facturas aún</p>
                    <p className="text-xs text-on-surface-variant/60 mt-1">Escanea el QR de tu primera factura DGI para comenzar</p>
                  </div>
                </div>
              )}
            </div>

            {invoices.length > 5 && (
              <div className="px-5 py-3 border-t border-outline-variant text-center text-xs text-on-surface-variant">
                Mostrando 5 de {invoices.length} facturas
              </div>
            )}
          </div>
        </section>
      </div>
    </div>
  )
}
