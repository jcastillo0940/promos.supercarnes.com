import { useEffect, useMemo, useRef, useState } from 'react'
import type { FormEvent, ReactNode } from 'react'
import type { Html5Qrcode } from 'html5-qrcode'
import { api } from '../api'
import './nivea.css'

type Phase = 'open' | 'upcoming' | 'closed' | 'unavailable'
type EntryMode = 'scan' | 'manual' | 'whatsapp'
type View = 'invoice' | 'form' | 'done' | 'status'

interface NiveaCampaign {
  name: string
  description: string | null
  status: string
  is_open: boolean
  phase: Phase
  starts_at: string | null
  ends_at: string | null
  invoice_issued_from: string | null
  invoice_issued_to: string | null
  winner_slots: number
  cash_prizes: Record<string, number>
  minimum_age: number
  story_min_chars: number
  story_max_chars: number
  logo_url: string | null
  hero_url: string | null
  product_images: string[]
  terms_text: string | null
  terms_version: string | null
  terms_pdf_url: string | null
  delivery_location: string | null
  delivery_requirements: string | null
}

interface InvoiceSummary {
  invoice_number: string | null
  purchase_amount: string
  issued_at: string
  branch_name: string | null
  product_check_status: 'matched' | 'undetermined'
  eligible_units: number
  matched_products: { description: string | null; quantity: number }[]
}

interface ParticipantForm {
  full_name: string
  cedula: string
  phone: string
  email: string
  birthdate: string
  nominee_name: string
  story: string
  declares_eligibility: boolean
  accepted_terms: boolean
}

const QR_READER_ID = 'nivea-qr-reader'
const CUFE_SHORT_PREFIX = 'FE01200000032812-2-249262-'
const WHATSAPP_URL = 'https://wa.me/50768982167?text=' + encodeURIComponent('Hola Super Carnes, quiero participar en Empollerando a Mamá con NIVEA y registrar mi factura.')
const SUPER_CARNES_LOGO = '/malta-vigor/reference/super-carnes-logo-360.webp'
const FONT_HREF = 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,600;1,700&display=swap'

const EMPTY_FORM: ParticipantForm = {
  full_name: '',
  cedula: '',
  phone: '',
  email: '',
  birthdate: '',
  nominee_name: '',
  story: '',
  declares_eligibility: false,
  accepted_terms: false,
}

const FALLBACK_PRODUCTS = ['/saba/pack-magenta.svg', '/saba/pack-purple.svg', '/saba/pack-lilac.svg', '/saba/pack-purple.svg', '/saba/pack-magenta.svg']
const FLOAT_SLOTS = ['top', 'far', 'right', 'bottom', 'bottom-blur'] as const

export default function NiveaPromo() {
  const [campaign, setCampaign] = useState<NiveaCampaign | null>(null)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [showTerms, setShowTerms] = useState(false)

  useNiveaHead()

  useEffect(() => {
    let cancelled = false
    api
      .get<{ data: NiveaCampaign }>('/public/nivea')
      .then((response) => {
        if (!cancelled) setCampaign(response.data.data)
      })
      .catch((error) => {
        if (!cancelled) setLoadError(errorMessage(error))
      })
    return () => {
      cancelled = true
    }
  }, [])

  const floatingProducts = useMemo(() => {
    const uploaded = campaign?.product_images ?? []
    const source = uploaded.length ? uploaded : FALLBACK_PRODUCTS
    return FLOAT_SLOTS.map((slot, index) => ({ slot, src: source[index % source.length] }))
  }, [campaign?.product_images])

  if (!campaign) {
    return (
      <div className="nivea-page nivea-page-loading">
        <NiveaWordmark />
        <p>{loadError ?? 'Cargando promoción…'}</p>
      </div>
    )
  }

  return (
    <div className="nivea-page">
      <div className="nivea-atmosphere" aria-hidden="true">
        <span className="nivea-beam nivea-beam-one" />
        <span className="nivea-beam nivea-beam-two" />
        {floatingProducts.map(({ slot, src }) => (
          <img key={slot} className={`nivea-float nivea-float-${slot}`} src={src} alt="" loading={slot === 'right' ? 'eager' : 'lazy'} decoding="async" />
        ))}
      </div>

      <main className="nivea-layout">
        <section className="nivea-hero">
          <div className="nivea-lockup">
            {campaign.logo_url ? <img className="nivea-logo-img" src={campaign.logo_url} alt="NIVEA" /> : <NiveaWordmark />}
            <i />
            <img className="nivea-sc-logo" src={SUPER_CARNES_LOGO} alt="Super Carnes" width="360" height="270" />
          </div>

          <p className="nivea-chip">NIVEA + Super Carnes</p>
          <h1 className="nivea-headline">
            <span>Empollerando</span>
            <strong>a Mamá</strong>
          </h1>
          <p className="nivea-subheadline">
            ¡{campaign.winner_slots} ganadoras! <em>con NIVEA y Super Carnes</em>
          </p>

          <div className="nivea-summary">
            <span aria-hidden="true"><IconFlower /></span>
            <p>
              Compra <strong>1 producto NIVEA</strong> en Super Carnes, registra tu factura y cuéntanos por qué tu mamá merece ser <strong>empollerada</strong>.
              ¡10 mamás recibirán una sesión fotográfica de pollera!
            </p>
          </div>
        </section>

        <section className="nivea-panel" aria-labelledby="nivea-panel-title">
          <ParticipationPanel campaign={campaign} onShowTerms={() => setShowTerms(true)} />
        </section>
      </main>

      <footer className="nivea-footer">
        <p>{validityText(campaign)} Aplica únicamente por la compra de productos de la marca NIVEA en Super Carnes.</p>
        <p>
          Una participación por persona. El registrante debe ser mayor de {campaign.minimum_age} años y residente de Panamá.{' '}
          <button type="button" className="nivea-inline-link" onClick={() => setShowTerms(true)}>Ver bases y condiciones</button>
        </p>
      </footer>

      {showTerms ? <TermsModal campaign={campaign} onClose={() => setShowTerms(false)} /> : null}
    </div>
  )
}

function ParticipationPanel({ campaign, onShowTerms }: { campaign: NiveaCampaign; onShowTerms: () => void }) {
  const [view, setView] = useState<View>('invoice')
  const [invoiceRaw, setInvoiceRaw] = useState('')
  const [invoice, setInvoice] = useState<InvoiceSummary | null>(null)
  const [result, setResult] = useState<{ first_name: string; status: string; message: string } | null>(null)

  if (!campaign.is_open) {
    return (
      <div className="nivea-card nivea-closed">
        <h2 id="nivea-panel-title">{closedTitle(campaign.phase)}</h2>
        <p>{closedText(campaign)}</p>
        {campaign.phase === 'closed' ? (
          <StatusLookup onBack={null} />
        ) : null}
      </div>
    )
  }

  if (view === 'status') {
    return <StatusLookup onBack={() => setView('invoice')} />
  }

  if (view === 'done' && result) {
    return (
      <div className="nivea-card nivea-done" role="status" aria-live="polite">
        <div className="nivea-done-icon" aria-hidden="true">✓</div>
        <p className="nivea-eyebrow">Participación registrada</p>
        <h2 id="nivea-panel-title">¡Gracias, {result.first_name}!</h2>
        <p>{result.message}</p>
        <p className="nivea-muted">Te enviamos la confirmación a tu correo. Si la mamá que postulaste resulta ganadora, te contactaremos por teléfono y correo.</p>
      </div>
    )
  }

  return (
    <div className="nivea-card">
      <Stepper current={view === 'invoice' ? 1 : 2} />
      {view === 'invoice' ? (
        <InvoiceStep
          onValidated={(raw, summary) => {
            setInvoiceRaw(raw)
            setInvoice(summary)
            setView('form')
          }}
          onStatus={() => setView('status')}
        />
      ) : invoice ? (
        <ParticipantStep
          campaign={campaign}
          onShowTerms={onShowTerms}
          invoice={invoice}
          invoiceRaw={invoiceRaw}
          onBack={() => {
            setInvoice(null)
            setInvoiceRaw('')
            setView('invoice')
          }}
          onDone={(data) => {
            setResult(data)
            setView('done')
          }}
        />
      ) : null}
    </div>
  )
}

function Stepper({ current }: { current: 1 | 2 }) {
  return (
    <ol className="nivea-stepper" aria-label="Pasos">
      <li className={current >= 1 ? 'is-active' : ''}><span>1</span> Tu factura</li>
      <li className={current >= 2 ? 'is-active' : ''}><span>2</span> Datos y la historia de mamá</li>
    </ol>
  )
}

function InvoiceStep({ onValidated, onStatus }: { onValidated: (raw: string, summary: InvoiceSummary) => void; onStatus: () => void }) {
  const [mode, setMode] = useState<EntryMode>('scan')
  const [scannerOn, setScannerOn] = useState(false)
  const [cufeTail, setCufeTail] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function resolve(raw: string) {
    setLoading(true)
    setError(null)
    try {
      const response = await api.post<{ data: InvoiceSummary }>('/public/nivea/resolve', { qr_raw_text: raw })
      onValidated(raw, response.data.data)
    } catch (resolveError) {
      setError(errorMessage(resolveError))
    } finally {
      setLoading(false)
    }
  }

  useQrScanner(scannerOn && mode === 'scan', (text) => {
    setScannerOn(false)
    void resolve(text)
  }, (scanError) => {
    setScannerOn(false)
    setError(scanError)
  })

  return (
    <div className="nivea-step">
      <p className="nivea-eyebrow">Paso 1</p>
      <h2 id="nivea-panel-title">Registra tu factura</h2>
      <p className="nivea-muted">Escanea tu factura de Super Carnes con al menos un producto NIVEA. Sin crear cuenta.</p>

      <div className="nivea-tabs" role="tablist">
        <button type="button" role="tab" aria-selected={mode === 'scan'} className={mode === 'scan' ? 'is-active' : ''} onClick={() => { setMode('scan'); setError(null) }}>Escanear QR</button>
        <button type="button" role="tab" aria-selected={mode === 'manual'} className={mode === 'manual' ? 'is-active' : ''} onClick={() => { setMode('manual'); setScannerOn(false); setError(null) }}>Escribir CUFE</button>
        <button type="button" role="tab" aria-selected={mode === 'whatsapp'} className={mode === 'whatsapp' ? 'is-active' : ''} onClick={() => { setMode('whatsapp'); setScannerOn(false); setError(null) }}>WhatsApp</button>
      </div>

      {mode === 'scan' ? (
        <div className="nivea-scan">
          {scannerOn ? (
            <>
              <div id={QR_READER_ID} className="nivea-scanner" />
              <button type="button" className="nivea-btn nivea-btn-ghost" onClick={() => setScannerOn(false)}>Cancelar</button>
            </>
          ) : (
            <button type="button" className="nivea-btn nivea-btn-primary nivea-btn-block" onClick={() => { setError(null); setScannerOn(true) }} disabled={loading}>
              {loading ? 'Validando factura…' : 'Activar cámara y escanear'}
            </button>
          )}
          <p className="nivea-help">Apunta la cámara al código QR impreso en tu factura.</p>
        </div>
      ) : mode === 'manual' ? (
        <form
          className="nivea-manual"
          onSubmit={(event) => {
            event.preventDefault()
            const digits = cufeTail.replace(/\D/g, '').slice(0, 60)
            if (digits.length >= 10) void resolve(`${CUFE_SHORT_PREFIX}${digits}`)
          }}
        >
          <label className="nivea-field">
            <span>Últimos números del CUFE</span>
            <input value={cufeTail} onChange={(event) => setCufeTail(event.target.value.replace(/\D/g, '').slice(0, 60))} inputMode="numeric" placeholder="Solo números, hasta 60" />
          </label>
          <button type="submit" className="nivea-btn nivea-btn-primary nivea-btn-block" disabled={loading || cufeTail.length < 10}>
            {loading ? 'Validando factura…' : 'Validar factura'}
          </button>
        </form>
      ) : (
        <div className="nivea-whatsapp">
          <p>Si no puedes escanear, envíanos por WhatsApp:</p>
          <ul>
            <li>Foto legible de tu factura (con el QR y los productos NIVEA)</li>
            <li>Tu nombre completo, cédula, teléfono, correo y fecha de nacimiento</li>
            <li>Nombre completo de la mamá que postulas</li>
            <li>Un párrafo contándonos por qué tu mamá merece ganar</li>
          </ul>
          <a className="nivea-btn nivea-btn-whatsapp nivea-btn-block" href={WHATSAPP_URL} target="_blank" rel="noopener noreferrer">Escribir por WhatsApp</a>
        </div>
      )}

      {loading && mode !== 'whatsapp' ? <p className="nivea-loading" role="status"><span className="nivea-spinner" /> Validando tu factura…</p> : null}
      {error ? <div className="nivea-alert" role="alert">{error}</div> : null}

      <button type="button" className="nivea-link" onClick={onStatus}>¿Ya participaste? Consulta tu participación</button>
    </div>
  )
}

function ParticipantStep({
  campaign,
  onShowTerms,
  invoice,
  invoiceRaw,
  onBack,
  onDone,
}: {
  campaign: NiveaCampaign
  onShowTerms: () => void
  invoice: InvoiceSummary
  invoiceRaw: string
  onBack: () => void
  onDone: (data: { first_name: string; status: string; message: string }) => void
}) {
  const [form, setForm] = useState<ParticipantForm>(EMPTY_FORM)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [generalError, setGeneralError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const storyLength = form.story.trim().length
  const maxBirthdate = useMemo(() => {
    const date = new Date()
    date.setFullYear(date.getFullYear() - campaign.minimum_age)
    return date.toISOString().slice(0, 10)
  }, [campaign.minimum_age])

  const update = <K extends keyof ParticipantForm>(key: K, value: ParticipantForm[K]) => {
    setForm((current) => ({ ...current, [key]: value }))
    setErrors((current) => {
      if (!current[key]) return current
      const next = { ...current }
      delete next[key]
      return next
    })
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const localErrors: Record<string, string> = {}
    if (storyLength < campaign.story_min_chars) localErrors.story = `Cuéntanos un poco más (mínimo ${campaign.story_min_chars} caracteres).`
    if (form.birthdate && form.birthdate > maxBirthdate) localErrors.birthdate = `Debes ser mayor de ${campaign.minimum_age} años para participar.`
    if (!form.nominee_name.trim() || !form.nominee_name.trim().includes(' ')) localErrors.nominee_name = 'Escribe el nombre y apellido de la mamá que postulas.'
    if (!form.declares_eligibility) localErrors.declares_eligibility = 'Confirma que la mamá postulada es madre y que cuentas con su autorización.'
    if (!form.accepted_terms) localErrors.accepted_terms = 'Debes aceptar las bases de la promoción.'
    if (Object.keys(localErrors).length) {
      setErrors(localErrors)
      return
    }

    setSubmitting(true)
    setGeneralError(null)
    try {
      const response = await api.post<{ data: { first_name: string; status: string; message: string } }>('/public/nivea/register', {
        ...form,
        qr_raw_text: invoiceRaw,
        terms_version: campaign.terms_version,
      })
      onDone(response.data.data)
    } catch (submitError) {
      const fieldErrors = validationErrors(submitError)
      if (fieldErrors) {
        const { qr_raw_text: invoiceError, campaign: campaignError, ...rest } = fieldErrors
        setErrors(rest)
        setGeneralError(invoiceError ?? campaignError ?? (Object.keys(rest).length ? 'Revisa los campos marcados.' : errorMessage(submitError)))
      } else {
        setGeneralError(errorMessage(submitError))
      }
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <form className="nivea-step" onSubmit={handleSubmit} noValidate>
      <div className="nivea-invoice-ok">
        <div>
          <p className="nivea-eyebrow">Factura válida</p>
          <strong>{invoice.branch_name ?? 'Super Carnes'} · {formatDate(invoice.issued_at)}</strong>
          {invoice.product_check_status === 'matched' ? (
            <span>
              {invoice.matched_products.length
                ? invoice.matched_products.map((item) => `${trimQuantity(item.quantity)} × ${item.description ?? 'Producto NIVEA'}`).join(' · ')
                : 'Producto NIVEA detectado'}
            </span>
          ) : (
            <span>Revisaremos manualmente que tu factura incluya un producto NIVEA.</span>
          )}
        </div>
        <button type="button" className="nivea-link" onClick={onBack}>Cambiar</button>
      </div>

      <p className="nivea-eyebrow">Paso 2</p>
      <h2 id="nivea-panel-title">Datos y la historia de mamá</h2>
      <p className="nivea-muted">Participas una sola vez con tu cédula. Revisa bien los datos antes de enviar.</p>

      <div className="nivea-section-label">Tus datos (registrante)</div>
      <div className="nivea-grid">
        <Field label="Nombre completo" error={errors.full_name} wide>
          <input value={form.full_name} onChange={(event) => update('full_name', event.target.value.replace(/[^\p{L}\s.'-]/gu, '').slice(0, 150))} autoComplete="name" placeholder="Tu nombre y apellido" required />
        </Field>
        <Field label="Número de cédula" error={errors.cedula}>
          <input value={form.cedula} onChange={(event) => update('cedula', event.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 20))} placeholder="8-123-4567" autoComplete="off" required />
        </Field>
        <Field label="Teléfono" error={errors.phone}>
          <input value={form.phone} onChange={(event) => update('phone', event.target.value.replace(/[^0-9-+ ]/g, '').slice(0, 15))} inputMode="tel" autoComplete="tel" placeholder="6123-4567" required />
        </Field>
        <Field label="Correo electrónico" error={errors.email}>
          <input value={form.email} onChange={(event) => update('email', event.target.value.trim())} type="email" autoComplete="email" placeholder="correo@dominio.com" required />
        </Field>
        <Field label="Fecha de nacimiento" error={errors.birthdate}>
          <input value={form.birthdate} onChange={(event) => update('birthdate', event.target.value)} type="date" max={maxBirthdate} min="1915-01-01" required />
        </Field>
      </div>

      <div className="nivea-section-label">Mamá que postulas</div>
      <div className="nivea-grid">
        <Field label="Nombre completo de la mamá" error={errors.nominee_name} wide>
          <input value={form.nominee_name} onChange={(event) => update('nominee_name', event.target.value.replace(/[^\p{L}\s.'-]/gu, '').slice(0, 150))} autoComplete="off" placeholder="Nombre y apellido de la mamá" required />
        </Field>
        <Field
          label="¿Por qué merece ser empollerada?"
          hint={`Cuéntanos su historia y por qué merece ganar. ${storyLength}/${campaign.story_max_chars} caracteres (mínimo ${campaign.story_min_chars}).`}
          error={errors.story}
          wide
        >
          <textarea value={form.story} onChange={(event) => update('story', event.target.value.slice(0, campaign.story_max_chars))} rows={6} placeholder="Escribe aquí la historia de tu mamá…" required />
        </Field>
      </div>

      <label className={`nivea-check ${errors.declares_eligibility ? 'has-error' : ''}`}>
        <input type="checkbox" checked={form.declares_eligibility} onChange={(event) => update('declares_eligibility', event.target.checked)} />
        <span>Confirmo que la persona postulada es madre y que cuento con su autorización para participar en esta promoción.</span>
      </label>
      {errors.declares_eligibility ? <p className="nivea-field-error">{errors.declares_eligibility}</p> : null}

      <label className={`nivea-check ${errors.accepted_terms ? 'has-error' : ''}`}>
        <input type="checkbox" checked={form.accepted_terms} onChange={(event) => update('accepted_terms', event.target.checked)} />
        <span>
          Acepto las{' '}
          <button type="button" className="nivea-inline-link" onClick={onShowTerms}>bases y condiciones de la promoción</button>
        </span>
      </label>
      {errors.accepted_terms ? <p className="nivea-field-error">{errors.accepted_terms}</p> : null}

      {generalError ? <div className="nivea-alert" role="alert">{generalError}</div> : null}

      <button type="submit" className="nivea-btn nivea-btn-primary nivea-btn-block" disabled={submitting}>
        {submitting ? 'Enviando participación…' : 'Enviar mi participación'}
      </button>
    </form>
  )
}

function StatusLookup({ onBack }: { onBack: (() => void) | null }) {
  const [cedula, setCedula] = useState('')
  const [email, setEmail] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [status, setStatus] = useState<{ first_name: string; nominee_name: string; status: string; status_label: string; registered_at: string; can_register_again: boolean } | null>(null)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setLoading(true)
    setError(null)
    setStatus(null)
    try {
      const response = await api.post<{ data: typeof status }>('/public/nivea/status', { cedula, email })
      setStatus(response.data.data)
    } catch (lookupError) {
      setError(errorMessage(lookupError))
    } finally {
      setLoading(false)
    }
  }

  return (
    <form className="nivea-card nivea-step nivea-status" onSubmit={handleSubmit}>
      <p className="nivea-eyebrow">Mi participación</p>
      <h2 id="nivea-panel-title">Consulta tu participación</h2>
      <p className="nivea-muted">Escribe la cédula y el correo con los que participaste.</p>
      <div className="nivea-grid">
        <Field label="Número de cédula">
          <input value={cedula} onChange={(event) => setCedula(event.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 20))} placeholder="8-123-4567" required />
        </Field>
        <Field label="Correo electrónico">
          <input value={email} onChange={(event) => setEmail(event.target.value.trim())} type="email" placeholder="correo@dominio.com" required />
        </Field>
      </div>
      {error ? <div className="nivea-alert" role="alert">{error}</div> : null}
      {status ? (
        <div className={`nivea-status-result is-${status.status}`} role="status">
          <strong>{status.first_name}, tu participación está: {status.status_label.toLowerCase()}</strong>
          {status.nominee_name ? <span>Mamá postulada: {status.nominee_name}</span> : null}
          <span>Registrada el {status.registered_at}.</span>
          {status.status === 'pending_review' ? <span>Estamos revisando tu factura. Te avisaremos por correo.</span> : null}
          {status.can_register_again ? <span>Puedes volver a participar con otra factura válida.</span> : null}
        </div>
      ) : null}
      <button type="submit" className="nivea-btn nivea-btn-primary nivea-btn-block" disabled={loading}>{loading ? 'Consultando…' : 'Consultar'}</button>
      {onBack ? <button type="button" className="nivea-link" onClick={onBack}>Volver a registrar mi factura</button> : null}
    </form>
  )
}

function TermsModal({ campaign, onClose }: { campaign: NiveaCampaign; onClose: () => void }) {
  useEffect(() => {
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [onClose])

  return (
    <div className="nivea-modal" role="dialog" aria-modal="true" aria-labelledby="nivea-terms-title" onClick={onClose}>
      <div className="nivea-modal-card" onClick={(event) => event.stopPropagation()}>
        <h2 id="nivea-terms-title">Bases y condiciones</h2>
        <TermsContent campaign={campaign} />
        <button type="button" className="nivea-btn nivea-btn-primary nivea-btn-block" onClick={onClose}>Cerrar</button>
      </div>
    </div>
  )
}

function TermsContent({ campaign }: { campaign: NiveaCampaign }) {
  return (
    <div className="nivea-terms-content">
      {campaign.terms_text ? (
        <div className="nivea-terms-text">{campaign.terms_text}</div>
      ) : (
        <ul>
          <li>Promoción de Super Carnes y NIVEA. {validityText(campaign)}</li>
          <li>Aplica únicamente por la compra de productos de la marca NIVEA en sucursales Super Carnes.</li>
          <li>Participan personas mayores de {campaign.minimum_age} años con cédula panameña o carnet de residente.</li>
          <li>El registrante postula a una madre de su elección y debe contar con su autorización.</li>
          <li>Una sola participación por cédula del registrante. No es acumulativa.</li>
          <li>La factura debe ser de Super Carnes, incluir al menos un producto NIVEA y no haber sido registrada antes.</li>
          <li>Se elegirán {campaign.winner_slots} ganadoras mediante un jurado de NIVEA y Super Carnes.</li>
          <li>El premio para cada mamá ganadora es una sesión fotográfica "empollerada". El premio no es transferible.</li>
          {campaign.delivery_location ? <li>Detalles de la sesión: {campaign.delivery_location}</li> : null}
          {campaign.delivery_requirements ? <li>{campaign.delivery_requirements}</li> : null}
        </ul>
      )}
      {campaign.terms_pdf_url ? (
        <a className="nivea-inline-link" href={campaign.terms_pdf_url} target="_blank" rel="noopener noreferrer">Ver reglamento completo (PDF)</a>
      ) : null}
    </div>
  )
}

function Field({ label, hint, error, wide, children }: { label: string; hint?: string; error?: string; wide?: boolean; children: ReactNode }) {
  return (
    <label className={`nivea-field ${wide ? 'is-wide' : ''} ${error ? 'has-error' : ''}`}>
      <span>{label}</span>
      {children}
      {hint ? <small>{hint}</small> : null}
      {error ? <em className="nivea-field-error">{error}</em> : null}
    </label>
  )
}

function NiveaWordmark() {
  return (
    <span className="nivea-wordmark" aria-label="NIVEA">
      NIVEA
    </span>
  )
}

function IconFlower() {
  return (
    <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="24" cy="24" r="5" />
      <path d="M24 8c0 5.5-3 8-3 8h6s-3-2.5-3-8z" />
      <path d="M24 40c0-5.5 3-8 3-8h-6s3 2.5 3 8z" />
      <path d="M8 24c5.5 0 8 3 8 3v-6s-2.5 3-8 3z" />
      <path d="M40 24c-5.5 0-8-3-8-3v6s2.5-3 8-3z" />
      <path d="M12.7 12.7c3.9 3.9 4 7.9 4 7.9l4.2-4.2s-4-0.1-8.2-3.7z" />
      <path d="M35.3 35.3c-3.9-3.9-4-7.9-4-7.9l-4.2 4.2s4 0.1 8.2 3.7z" />
      <path d="M35.3 12.7c-3.9 3.9-7.9 4-7.9 4l4.2 4.2s0.1-4 3.7-8.2z" />
      <path d="M12.7 35.3c3.9-3.9 7.9-4 7.9-4l-4.2-4.2s-0.1 4-3.7 8.2z" />
    </svg>
  )
}

function useQrScanner(active: boolean, onDecoded: (text: string) => void, onError: (message: string) => void) {
  const handlers = useRef({ onDecoded, onError })
  handlers.current = { onDecoded, onError }

  useEffect(() => {
    if (!active) return undefined
    let scanner: Html5Qrcode | null = null
    let stopped = false
    let handled = false
    let frameCount = 0

    console.log('[NiveaQR] useQrScanner activado', {
      isSecureContext: window.isSecureContext,
      protocol: window.location.protocol,
      hasMediaDevices: !!navigator.mediaDevices,
      hasGetUserMedia: !!(navigator.mediaDevices?.getUserMedia),
      userAgent: navigator.userAgent,
    })

    const stop = async () => {
      if (!scanner) return
      try { await scanner.stop() } catch { /* ya detenido */ }
      try { scanner.clear() } catch { /* nada que limpiar */ }
    }

    void (async () => {
      try {
        console.log('[NiveaQR] Importando html5-qrcode…')
        const module = await import('html5-qrcode')
        console.log('[NiveaQR] Módulo html5-qrcode cargado OK')
        if (stopped) { console.log('[NiveaQR] Abortado antes de iniciar (stopped=true)'); return }

        const containerEl = document.getElementById(QR_READER_ID)
        console.log('[NiveaQR] Contenedor DOM:', containerEl ? `encontrado (${containerEl.offsetWidth}×${containerEl.offsetHeight})` : 'NO ENCONTRADO')

        scanner = new module.Html5Qrcode(QR_READER_ID, { verbose: false, formatsToSupport: [
          module.Html5QrcodeSupportedFormats.QR_CODE,
          module.Html5QrcodeSupportedFormats.DATA_MATRIX,
          module.Html5QrcodeSupportedFormats.PDF_417,
          module.Html5QrcodeSupportedFormats.AZTEC,
        ] })
        console.log('[NiveaQR] Instancia Html5Qrcode creada')

        console.log('[NiveaQR] Llamando scanner.start()…')
        await scanner.start(
          { facingMode: 'environment' },
          {
            fps: 12,
            disableFlip: true,
            aspectRatio: 4 / 3,
            qrbox: (width, height) => {
              const edge = Math.floor(Math.max(190, Math.min(width, height) * 0.78))
              return { width: Math.min(edge, 330), height: Math.min(edge, 330) }
            },
          },
          (text) => {
            console.log('[NiveaQR] ✅ QR decodificado:', text)
            if (handled) return
            handled = true
            void stop()
            handlers.current.onDecoded(text)
          },
          (errorMsg) => {
            frameCount++
            if (frameCount === 1 || frameCount % 60 === 0) {
              console.log(`[NiveaQR] frame #${frameCount} — sin QR detectado (último: ${errorMsg ?? 'n/a'})`)
            }
          },
        )
        const track = (scanner as unknown as { videoElement?: HTMLVideoElement })
          ?.videoElement?.srcObject instanceof MediaStream
            ? ((scanner as unknown as { videoElement?: HTMLVideoElement }).videoElement!.srcObject as MediaStream).getVideoTracks()[0]
            : null
        const settings = track?.getSettings()
        console.log('[NiveaQR] scanner.start() resolvió — cámara activa', settings
          ? `${settings.width ?? '?'}×${settings.height ?? '?'}`
          : '(resolución desconocida)'
        )
      } catch (err) {
        console.error('[NiveaQR] ❌ Error al iniciar escáner:', err)
        const detail = err instanceof Error ? `${err.name}: ${err.message}` : String(err)
        console.error('[NiveaQR] Detalle del error:', detail)
        if (!stopped) handlers.current.onError('No pudimos activar la cámara. Revisa los permisos o escribe el CUFE manualmente.')
      }
    })()

    return () => {
      stopped = true
      console.log('[NiveaQR] cleanup — deteniendo escáner')
      void stop()
    }
  }, [active])
}

function useNiveaHead() {
  useEffect(() => {
    const title = 'Empollerando a Mamá con NIVEA y Super Carnes'
    const description = 'Compra 1 producto NIVEA en Super Carnes, registra tu factura y cuéntanos por qué tu mamá merece ser empollerada. ¡10 ganadoras!'
    document.title = title
    setMeta('name', 'description', description)
    setMeta('property', 'og:title', title)
    setMeta('property', 'og:description', description)
    setMeta('property', 'og:url', `${window.location.origin}/nivea`)
    setMeta('name', 'twitter:title', title)
    setMeta('name', 'twitter:description', description)
    setMeta('name', 'theme-color', '#003087')
    const canonical = document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]')
    if (canonical) canonical.href = `${window.location.origin}/nivea`

    if (!document.getElementById('nivea-fonts')) {
      const link = document.createElement('link')
      link.id = 'nivea-fonts'
      link.rel = 'stylesheet'
      link.href = FONT_HREF
      document.head.appendChild(link)
    }
  }, [])
}

function setMeta(attribute: 'name' | 'property', key: string, content: string) {
  let element = document.head.querySelector<HTMLMetaElement>(`meta[${attribute}="${key}"]`)
  if (!element) {
    element = document.createElement('meta')
    element.setAttribute(attribute, key)
    document.head.appendChild(element)
  }
  element.content = content
}

function validityText(campaign: NiveaCampaign) {
  if (!campaign.invoice_issued_from || !campaign.invoice_issued_to) return ''
  return `Promoción válida del ${longDate(campaign.invoice_issued_from, false)} al ${longDate(campaign.invoice_issued_to, true)}.`
}

function closedTitle(phase: Phase) {
  if (phase === 'upcoming') return 'La promoción inicia muy pronto'
  if (phase === 'closed') return 'La promoción finalizó'
  return 'Muy pronto podrás participar'
}

function closedText(campaign: NiveaCampaign) {
  if (campaign.phase === 'upcoming' && campaign.starts_at) return `Podrás registrar tu factura a partir del ${longDate(campaign.starts_at, true)}.`
  if (campaign.phase === 'closed') return '¡Gracias por participar! El 16 de noviembre anunciaremos a las 10 mamás ganadoras. Puedes consultar tu participación aquí abajo.'
  return 'Estamos preparando todo para que registres tu factura. Vuelve en unas horas.'
}

const MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre']

function longDate(value: string, withYear: boolean) {
  const [year, month, day] = value.slice(0, 10).split('-').map(Number)
  if (!year || !month || !day) return value
  return `${day} de ${MONTHS[month - 1]}${withYear ? ` de ${year}` : ''}`
}

function formatDate(value: string) {
  const [year, month, day] = value.slice(0, 10).split('-')
  return day && month && year ? `${day}/${month}/${year}` : value
}

function trimQuantity(value: number) {
  return Number.isInteger(value) ? String(value) : value.toFixed(2)
}

function validationErrors(error: unknown): Record<string, string> | null {
  const errors = (error as { response?: { status?: number; data?: { errors?: Record<string, string[]> } } })?.response?.data?.errors
  if (!errors) return null
  return Object.fromEntries(Object.entries(errors).map(([key, messages]) => [key, messages[0] ?? 'Dato inválido.']))
}

function errorMessage(error: unknown) {
  const response = (error as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } })?.response
  if (response?.status === 429) return 'Hiciste demasiados intentos seguidos. Espera un minuto y vuelve a intentarlo.'
  const errors = response?.data?.errors
  if (errors) {
    const first = Object.values(errors)[0]?.[0]
    if (first) return first
  }
  if (response?.data?.message) return response.data.message
  return 'No pudimos conectar. Revisa tu conexión e inténtalo de nuevo.'
}
