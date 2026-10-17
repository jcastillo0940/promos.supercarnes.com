import { useEffect, useMemo, useRef, useState } from 'react'
import type { FormEvent, ReactNode } from 'react'
import type { Html5Qrcode } from 'html5-qrcode'
import { api } from '../api'
import './saba.css'

type Phase = 'open' | 'upcoming' | 'closed' | 'unavailable'
type EntryMode = 'scan' | 'manual' | 'whatsapp'
type View = 'invoice' | 'form' | 'done' | 'status'

interface SabaCampaign {
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
  story: string
  declares_eligibility: boolean
  accepted_terms: boolean
}

const QR_READER_ID = 'saba-qr-reader'
const CUFE_SHORT_PREFIX = 'FE01200000032812-2-249262-'
const WHATSAPP_URL = 'https://wa.me/50768982167?text=' + encodeURIComponent('Hola Super Carnes, quiero participar en Emprende tu Sueño con Saba y registrar mi factura.')
const SUPER_CARNES_LOGO = '/malta-vigor/reference/super-carnes-logo-360.webp'
const FONT_HREF = 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,600;1,700&display=swap'

const EMPTY_FORM: ParticipantForm = {
  full_name: '',
  cedula: '',
  phone: '',
  email: '',
  birthdate: '',
  story: '',
  declares_eligibility: false,
  accepted_terms: false,
}

const FALLBACK_PRODUCTS = ['/saba/pack-magenta.svg', '/saba/pack-purple.svg', '/saba/pack-lilac.svg', '/saba/pack-purple.svg', '/saba/pack-magenta.svg']
const FLOAT_SLOTS = ['top', 'far', 'right', 'bottom', 'bottom-blur'] as const

export default function SabaPromo() {
  const [campaign, setCampaign] = useState<SabaCampaign | null>(null)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [showTerms, setShowTerms] = useState(false)

  useSabaHead()

  useEffect(() => {
    let cancelled = false
    api
      .get<{ data: SabaCampaign }>('/public/saba')
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
      <div className="saba-page saba-page-loading">
        <SabaWordmark />
        <p>{loadError ?? 'Cargando promoción…'}</p>
      </div>
    )
  }

  const prizes = Object.entries(campaign.cash_prizes ?? {}).sort(([a], [b]) => Number(a) - Number(b))

  return (
    <div className="saba-page">
      <div className="saba-atmosphere" aria-hidden="true">
        <span className="saba-beam saba-beam-one" />
        <span className="saba-beam saba-beam-two" />
        {floatingProducts.map(({ slot, src }) => (
          <img key={slot} className={`saba-float saba-float-${slot}`} src={src} alt="" loading={slot === 'right' ? 'eager' : 'lazy'} decoding="async" />
        ))}
      </div>

      <main className="saba-layout">
        <section className="saba-hero">
          <div className="saba-lockup">
            {campaign.logo_url ? <img className="saba-logo-img" src={campaign.logo_url} alt="Saba" /> : <SabaWordmark />}
            <i />
            <img className="saba-sc-logo" src={SUPER_CARNES_LOGO} alt="Super Carnes" width="360" height="270" />
          </div>

          <p className="saba-chip">Saba + Super Carnes</p>
          <h1 className="saba-headline">
            <span>Emprende tu</span>
            <strong>Sueño</strong>
          </h1>
          <p className="saba-subheadline">
            ¡{campaign.winner_slots} ganadoras! <em>con Saba y Super Carnes</em>
          </p>

          <div className="saba-summary">
            <span aria-hidden="true"><IconTent /></span>
            <p>
              Compra <strong>1 producto Saba</strong> en Super Carnes, registra tu factura y cuéntanos por qué deberías ganar.
              Cada ganadora recibe una <strong>tolda rotulada con su logo</strong>, artículos promocionales y capacitación financiera gratis.
            </p>
          </div>

          {prizes.length ? (
            <div className="saba-cash" aria-label="Premios en efectivo">
              {prizes.map(([place, amount]) => (
                <div key={place} className={`saba-cash-item ${place === '1' ? 'is-first' : ''}`}>
                  <span>{place}° lugar</span>
                  <strong>${formatMoney(amount)}</strong>
                </div>
              ))}
            </div>
          ) : null}
        </section>

        <section className="saba-panel" aria-labelledby="saba-panel-title">
          <ParticipationPanel campaign={campaign} onShowTerms={() => setShowTerms(true)} />
        </section>
      </main>

      <footer className="saba-footer">
        <p>{validityText(campaign)} Aplica únicamente por la compra de productos de la marca SABA en Super Carnes.</p>
        <p>
          Participación única por persona. Exclusiva para mujeres mayores de {campaign.minimum_age} años.{' '}
          <button type="button" className="saba-inline-link" onClick={() => setShowTerms(true)}>Ver bases y condiciones</button>
        </p>
      </footer>

      {showTerms ? <TermsModal campaign={campaign} onClose={() => setShowTerms(false)} /> : null}
    </div>
  )
}

function ParticipationPanel({ campaign, onShowTerms }: { campaign: SabaCampaign; onShowTerms: () => void }) {
  const [view, setView] = useState<View>('invoice')
  const [invoiceRaw, setInvoiceRaw] = useState('')
  const [invoice, setInvoice] = useState<InvoiceSummary | null>(null)
  const [result, setResult] = useState<{ first_name: string; status: string; message: string } | null>(null)

  if (!campaign.is_open) {
    return (
      <div className="saba-card saba-closed">
        <h2 id="saba-panel-title">{closedTitle(campaign.phase)}</h2>
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
      <div className="saba-card saba-done" role="status" aria-live="polite">
        <div className="saba-done-icon" aria-hidden="true">✓</div>
        <p className="saba-eyebrow">Participación registrada</p>
        <h2 id="saba-panel-title">¡Gracias, {result.first_name}!</h2>
        <p>{result.message}</p>
        <p className="saba-muted">Te enviamos la confirmación a tu correo. Si resultas ganadora te contactaremos por teléfono y correo.</p>
      </div>
    )
  }

  return (
    <div className="saba-card">
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
    <ol className="saba-stepper" aria-label="Pasos">
      <li className={current >= 1 ? 'is-active' : ''}><span>1</span> Tu factura</li>
      <li className={current >= 2 ? 'is-active' : ''}><span>2</span> Tus datos y tu historia</li>
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
      const response = await api.post<{ data: InvoiceSummary }>('/public/saba/resolve', { qr_raw_text: raw })
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
    <div className="saba-step">
      <p className="saba-eyebrow">Paso 1</p>
      <h2 id="saba-panel-title">Registra tu factura</h2>
      <p className="saba-muted">Escanea tu factura de Super Carnes con al menos un producto Saba. Sin crear cuenta.</p>

      <div className="saba-tabs" role="tablist">
        <button type="button" role="tab" aria-selected={mode === 'scan'} className={mode === 'scan' ? 'is-active' : ''} onClick={() => { setMode('scan'); setError(null) }}>Escanear QR</button>
        <button type="button" role="tab" aria-selected={mode === 'manual'} className={mode === 'manual' ? 'is-active' : ''} onClick={() => { setMode('manual'); setScannerOn(false); setError(null) }}>Escribir CUFE</button>
        <button type="button" role="tab" aria-selected={mode === 'whatsapp'} className={mode === 'whatsapp' ? 'is-active' : ''} onClick={() => { setMode('whatsapp'); setScannerOn(false); setError(null) }}>WhatsApp</button>
      </div>

      {mode === 'scan' ? (
        <div className="saba-scan">
          {scannerOn ? (
            <>
              <div id={QR_READER_ID} className="saba-scanner" />
              <button type="button" className="saba-btn saba-btn-ghost" onClick={() => setScannerOn(false)}>Cancelar</button>
            </>
          ) : (
            <button type="button" className="saba-btn saba-btn-primary saba-btn-block" onClick={() => { setError(null); setScannerOn(true) }} disabled={loading}>
              {loading ? 'Validando factura…' : 'Activar cámara y escanear'}
            </button>
          )}
          <p className="saba-help">Apunta la cámara al código QR impreso en tu factura.</p>
        </div>
      ) : mode === 'manual' ? (
        <form
          className="saba-manual"
          onSubmit={(event) => {
            event.preventDefault()
            const digits = cufeTail.replace(/\D/g, '').slice(0, 60)
            if (digits.length >= 10) void resolve(`${CUFE_SHORT_PREFIX}${digits}`)
          }}
        >
          <label className="saba-field">
            <span>Últimos números del CUFE</span>
            <div style={{display:'flex',flexDirection:'column',borderRadius:'12px',border:'1px solid rgba(255,127,191,0.32)',background:'rgba(10,2,16,0.6)',overflow:'hidden'}}>
              <span style={{padding:'5px 14px 4px',fontSize:'10px',color:'rgba(255,255,255,0.35)',fontFamily:'monospace',borderBottom:'1px solid rgba(255,127,191,0.12)',letterSpacing:'0.01em',userSelect:'none'}}>
                FE01200000032812-2-249262-
              </span>
              <input
                value={cufeTail}
                onChange={(event) => setCufeTail(event.target.value.replace(/\D/g, '').slice(0, 40))}
                inputMode="numeric"
                maxLength={40}
                placeholder="63000XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
                style={{border:'none',background:'transparent',borderRadius:0,boxShadow:'none',minHeight:'auto',padding:'10px 14px',fontFamily:'monospace'}}
              />
            </div>
          </label>
          <button type="submit" className="saba-btn saba-btn-primary saba-btn-block" disabled={loading || cufeTail.length < 10}>
            {loading ? 'Validando factura…' : 'Validar factura'}
          </button>
        </form>
      ) : (
        <div className="saba-whatsapp">
          <p>Si no puedes escanear, envíanos por WhatsApp:</p>
          <ul>
            <li>Foto legible de tu factura (con el QR y los productos Saba)</li>
            <li>Nombre completo, cédula, teléfono, correo y fecha de nacimiento</li>
            <li>Un párrafo contándonos por qué deberías ganar</li>
          </ul>
          <a className="saba-btn saba-btn-whatsapp saba-btn-block" href={WHATSAPP_URL} target="_blank" rel="noopener noreferrer">Escribir por WhatsApp</a>
        </div>
      )}

      {loading && mode !== 'whatsapp' ? <p className="saba-loading" role="status"><span className="saba-spinner" /> Validando tu factura…</p> : null}
      {error ? <div className="saba-alert" role="alert">{error}</div> : null}

      <button type="button" className="saba-link" onClick={onStatus}>¿Ya participaste? Consulta tu participación</button>
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
  campaign: SabaCampaign
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
    if (!form.declares_eligibility) localErrors.declares_eligibility = 'Confirma que cumples los requisitos.'
    if (!form.accepted_terms) localErrors.accepted_terms = 'Debes aceptar las bases de la promoción.'
    if (Object.keys(localErrors).length) {
      setErrors(localErrors)
      return
    }

    setSubmitting(true)
    setGeneralError(null)
    try {
      const response = await api.post<{ data: { first_name: string; status: string; message: string } }>('/public/saba/register', {
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
    <form className="saba-step" onSubmit={handleSubmit} noValidate>
      <div className="saba-invoice-ok">
        <div>
          <p className="saba-eyebrow">Factura válida</p>
          <strong>{invoice.branch_name ?? 'Super Carnes'} · {formatDate(invoice.issued_at)}</strong>
          {invoice.product_check_status === 'matched' ? (
            <span>
              {invoice.matched_products.length
                ? invoice.matched_products.map((item) => `${trimQuantity(item.quantity)} × ${item.description ?? 'Producto Saba'}`).join(' · ')
                : 'Producto Saba detectado'}
            </span>
          ) : (
            <span>Revisaremos manualmente que tu factura incluya un producto Saba.</span>
          )}
        </div>
        <button type="button" className="saba-link" onClick={onBack}>Cambiar</button>
      </div>

      <p className="saba-eyebrow">Paso 2</p>
      <h2 id="saba-panel-title">Tus datos y tu historia</h2>
      <p className="saba-muted">Participas una sola vez con tu cédula. Revisa bien tus datos antes de enviar.</p>

      <div className="saba-grid">
        <Field label="Nombre completo" error={errors.full_name} wide>
          <input value={form.full_name} onChange={(event) => update('full_name', event.target.value.replace(/[^\p{L}\s.'-]/gu, '').slice(0, 150))} autoComplete="name" placeholder="Nombre y apellido" required />
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
        <Field
          label="¿Por qué mereces ganar?"
          hint={`Cuéntanos de tu sueño o de tu emprendimiento. ${storyLength}/${campaign.story_max_chars} caracteres (mínimo ${campaign.story_min_chars}).`}
          error={errors.story}
          wide
        >
          <textarea value={form.story} onChange={(event) => update('story', event.target.value.slice(0, campaign.story_max_chars))} rows={6} placeholder="Escribe aquí tu historia…" required />
        </Field>
      </div>

      <label className={`saba-check ${errors.declares_eligibility ? 'has-error' : ''}`}>
        <input type="checkbox" checked={form.declares_eligibility} onChange={(event) => update('declares_eligibility', event.target.checked)} />
        <span>Confirmo que soy mujer, mayor de {campaign.minimum_age} años y que los datos ingresados son verdaderos.</span>
      </label>
      {errors.declares_eligibility ? <p className="saba-field-error">{errors.declares_eligibility}</p> : null}

      <label className={`saba-check ${errors.accepted_terms ? 'has-error' : ''}`}>
        <input type="checkbox" checked={form.accepted_terms} onChange={(event) => update('accepted_terms', event.target.checked)} />
        <span>
          Acepto las{' '}
          <button type="button" className="saba-inline-link" onClick={onShowTerms}>bases y condiciones de la promoción</button>
        </span>
      </label>
      {errors.accepted_terms ? <p className="saba-field-error">{errors.accepted_terms}</p> : null}

      {generalError ? <div className="saba-alert" role="alert">{generalError}</div> : null}

      <button type="submit" className="saba-btn saba-btn-primary saba-btn-block" disabled={submitting}>
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
  const [status, setStatus] = useState<{ first_name: string; status: string; status_label: string; registered_at: string; can_register_again: boolean } | null>(null)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setLoading(true)
    setError(null)
    setStatus(null)
    try {
      const response = await api.post<{ data: typeof status }>('/public/saba/status', { cedula, email })
      setStatus(response.data.data)
    } catch (lookupError) {
      setError(errorMessage(lookupError))
    } finally {
      setLoading(false)
    }
  }

  return (
    <form className="saba-card saba-step saba-status" onSubmit={handleSubmit}>
      <p className="saba-eyebrow">Mi participación</p>
      <h2 id="saba-panel-title">Consulta tu participación</h2>
      <p className="saba-muted">Escribe la cédula y el correo con los que participaste.</p>
      <div className="saba-grid">
        <Field label="Número de cédula">
          <input value={cedula} onChange={(event) => setCedula(event.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 20))} placeholder="8-123-4567" required />
        </Field>
        <Field label="Correo electrónico">
          <input value={email} onChange={(event) => setEmail(event.target.value.trim())} type="email" placeholder="correo@dominio.com" required />
        </Field>
      </div>
      {error ? <div className="saba-alert" role="alert">{error}</div> : null}
      {status ? (
        <div className={`saba-status-result is-${status.status}`} role="status">
          <strong>{status.first_name}, tu participación está: {status.status_label.toLowerCase()}</strong>
          <span>Registrada el {status.registered_at}.</span>
          {status.status === 'pending_review' ? <span>Estamos revisando tu factura. Te avisaremos por correo.</span> : null}
          {status.can_register_again ? <span>Puedes volver a participar con otra factura válida.</span> : null}
        </div>
      ) : null}
      <button type="submit" className="saba-btn saba-btn-primary saba-btn-block" disabled={loading}>{loading ? 'Consultando…' : 'Consultar'}</button>
      {onBack ? <button type="button" className="saba-link" onClick={onBack}>Volver a registrar mi factura</button> : null}
    </form>
  )
}

function TermsModal({ campaign, onClose }: { campaign: SabaCampaign; onClose: () => void }) {
  useEffect(() => {
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [onClose])

  return (
    <div className="saba-modal" role="dialog" aria-modal="true" aria-labelledby="saba-terms-title" onClick={onClose}>
      <div className="saba-modal-card" onClick={(event) => event.stopPropagation()}>
        <h2 id="saba-terms-title">Bases y condiciones</h2>
        <TermsContent campaign={campaign} />
        <button type="button" className="saba-btn saba-btn-primary saba-btn-block" onClick={onClose}>Cerrar</button>
      </div>
    </div>
  )
}

function TermsContent({ campaign }: { campaign: SabaCampaign }) {
  return (
    <div className="saba-terms-content">
      {campaign.terms_text ? (
        <div className="saba-terms-text">{campaign.terms_text}</div>
      ) : (
        <ul>
          <li>Promoción de Super Carnes y Saba. {validityText(campaign)}</li>
          <li>Aplica únicamente por la compra de productos de la marca SABA en sucursales Super Carnes.</li>
          <li>Participan mujeres mayores de {campaign.minimum_age} años con cédula panameña o documento válido.</li>
          <li>Una sola participación por persona (por cédula). No es acumulativa.</li>
          <li>La factura debe ser de Super Carnes, incluir al menos un producto Saba y no haber sido registrada antes.</li>
          <li>Se elegirán {campaign.winner_slots} ganadoras según su historia. Cada una recibe 1 tolda rotulada con su logo, artículos promocionales para su negocio y capacitación financiera gratis.</li>
          {Object.keys(campaign.cash_prizes ?? {}).length ? (
            <li>
              Premios especiales en efectivo:{' '}
              {Object.entries(campaign.cash_prizes).map(([place, amount]) => `${place}° lugar $${formatMoney(amount)}`).join(', ')}.
            </li>
          ) : null}
          {campaign.delivery_location ? <li>Entrega de premios: {campaign.delivery_location}</li> : null}
          {campaign.delivery_requirements ? <li>{campaign.delivery_requirements}</li> : null}
        </ul>
      )}
      {campaign.terms_pdf_url ? (
        <a className="saba-inline-link" href={campaign.terms_pdf_url} target="_blank" rel="noopener noreferrer">Ver reglamento completo (PDF)</a>
      ) : null}
    </div>
  )
}

function Field({ label, hint, error, wide, children }: { label: string; hint?: string; error?: string; wide?: boolean; children: ReactNode }) {
  return (
    <label className={`saba-field ${wide ? 'is-wide' : ''} ${error ? 'has-error' : ''}`}>
      <span>{label}</span>
      {children}
      {hint ? <small>{hint}</small> : null}
      {error ? <em className="saba-field-error">{error}</em> : null}
    </label>
  )
}

function SabaWordmark() {
  return (
    <span className="saba-wordmark" aria-label="Saba">
      saba
    </span>
  )
}

function IconTent() {
  return (
    <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
      <path d="M6 18h36l-4-8H10z" />
      <path d="M6 18c0 3 3 5 6 5s6-2 6-5c0 3 3 5 6 5s6-2 6-5c0 3 3 5 6 5s6-2 6-5" />
      <path d="M10 23v17M38 23v17M6 40h36" />
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

    const stop = async () => {
      if (!scanner) return
      try { await scanner.stop() } catch { /* ya detenido */ }
      try { scanner.clear() } catch { /* nada que limpiar */ }
    }

    void (async () => {
      try {
        const module = await import('html5-qrcode')
        if (stopped) return
        scanner = new module.Html5Qrcode(QR_READER_ID, { verbose: false, formatsToSupport: [
          module.Html5QrcodeSupportedFormats.QR_CODE,
          module.Html5QrcodeSupportedFormats.DATA_MATRIX,
          module.Html5QrcodeSupportedFormats.PDF_417,
          module.Html5QrcodeSupportedFormats.AZTEC,
        ] })
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
            if (handled) return
            handled = true
            void stop()
            handlers.current.onDecoded(text)
          },
          () => undefined,
        )
      } catch {
        if (!stopped) handlers.current.onError('No pudimos activar la cámara. Revisa los permisos o escribe el CUFE manualmente.')
      }
    })()

    return () => {
      stopped = true
      void stop()
    }
  }, [active])
}

function useSabaHead() {
  useEffect(() => {
    const title = 'Emprende tu Sueño con Saba y Super Carnes'
    const description = 'Compra 1 producto Saba en Super Carnes, registra tu factura y cuéntanos por qué deberías ganar. ¡50 ganadoras!'
    document.title = title
    setMeta('name', 'description', description)
    setMeta('property', 'og:title', title)
    setMeta('property', 'og:description', description)
    setMeta('property', 'og:url', `${window.location.origin}/saba`)
    setMeta('name', 'twitter:title', title)
    setMeta('name', 'twitter:description', description)
    setMeta('name', 'theme-color', '#14041f')
    const canonical = document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]')
    if (canonical) canonical.href = `${window.location.origin}/saba`

    if (!document.getElementById('saba-fonts')) {
      const link = document.createElement('link')
      link.id = 'saba-fonts'
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

function validityText(campaign: SabaCampaign) {
  if (!campaign.invoice_issued_from || !campaign.invoice_issued_to) return ''
  return `Promoción válida del ${longDate(campaign.invoice_issued_from, false)} al ${longDate(campaign.invoice_issued_to, true)}.`
}

function closedTitle(phase: Phase) {
  if (phase === 'upcoming') return 'La promoción inicia muy pronto'
  if (phase === 'closed') return 'La promoción finalizó'
  return 'Muy pronto podrás participar'
}

function closedText(campaign: SabaCampaign) {
  if (campaign.phase === 'upcoming' && campaign.starts_at) return `Podrás registrar tu factura a partir del ${longDate(campaign.starts_at, true)}.`
  if (campaign.phase === 'closed') return '¡Gracias por participar! Pronto anunciaremos a las ganadoras. Puedes consultar tu participación aquí abajo.'
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

function formatMoney(value: number) {
  return Number(value).toLocaleString('en-US', { maximumFractionDigits: 0 })
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
