import { useEffect, useState } from 'react'
import type { ChangeEvent, FormEvent } from 'react'
import type { Branch, User } from '../types'
import { optimizeAvatarFile } from '../utils/avatarUpload'

type CuentaSection = 'perfil' | 'terminos'

function documentTypeLabel(documentType: User['document_type']) {
  if (documentType === 'cedula') return 'Cedula'
  if (documentType === 'residente') return 'Residente'
  return 'Pasaporte'
}

function formatBirthdate(dateValue: string | null | undefined) {
  if (!dateValue) return 'No registrada'

  const date = new Date(dateValue)
  if (Number.isNaN(date.getTime())) return dateValue

  return date.toLocaleDateString('es-PA', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    timeZone: 'America/Panama',
  })
}

export function CuentaView({
  user,
  saving,
  branches,
  termsText,
  section,
  onSectionChange,
  onSave,
}: {
  user: User
  saving: boolean
  branches: Branch[]
  termsText: string
  section: CuentaSection
  onSectionChange: (section: CuentaSection) => void
  onSave: (payload: { email: string; phone: string; avatarFile: File | null; branchId: string }) => Promise<void>
}) {
  const [email, setEmail] = useState(user.email)
  const [phone, setPhone] = useState(user.phone ?? '')
  const [branchId, setBranchId] = useState(String(user.branch?.id ?? ''))
  const [avatarFile, setAvatarFile] = useState<File | null>(null)
  const [avatarPreview, setAvatarPreview] = useState<string | null>(user.avatar_url ?? null)

  useEffect(() => {
    setEmail(user.email)
    setPhone(user.phone ?? '')
    setBranchId(String(user.branch?.id ?? ''))
    setAvatarFile(null)
    setAvatarPreview(user.avatar_url ?? null)
  }, [user.email, user.phone, user.avatar_url, user.branch])

  useEffect(() => {
    if (!avatarFile) return

    const objectUrl = URL.createObjectURL(avatarFile)
    setAvatarPreview(objectUrl)

    return () => URL.revokeObjectURL(objectUrl)
  }, [avatarFile])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    await onSave({ email, phone, avatarFile, branchId })
  }

  async function handleAvatarChange(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0] ?? null

    if (!file) {
      setAvatarFile(null)
      return
    }

    try {
      const optimized = await optimizeAvatarFile(file, 'avatar-profile')
      setAvatarFile(optimized)
    } catch {
      setAvatarFile(file)
    }
  }

  return (
    <section className="cuenta-view">
      <header className="cuenta-hero">
        <div className="cuenta-hero-copy">
          <span className="cuenta-kicker">Mi cuenta</span>
          <h1>{section === 'terminos' ? 'Terminos y condiciones' : 'Datos personales'}</h1>
          <p>
            {section === 'terminos'
              ? 'Consulta el documento legal completo de la promocion dentro de tu cuenta.'
              : 'Administra tu informacion de cuenta y manten tus datos al dia.'}
          </p>
        </div>
      </header>

      <nav className="cuenta-section-tabs" aria-label="Secciones de mi cuenta">
        <button
          className={section === 'perfil' ? 'cuenta-section-tab active' : 'cuenta-section-tab'}
          type="button"
          onClick={() => onSectionChange('perfil')}
        >
          Perfil
        </button>
        <button
          className={section === 'terminos' ? 'cuenta-section-tab active' : 'cuenta-section-tab'}
          type="button"
          onClick={() => onSectionChange('terminos')}
        >
          Terminos y condiciones
        </button>
      </nav>

      {section === 'terminos' ? (
        <section className="cuenta-panel cuenta-panel-terms">
          <div className="cuenta-panel-head">
            <span className="cuenta-kicker">Documento legal</span>
            <h2>Terminos y condiciones</h2>
          </div>
          <pre className="cuenta-terms-text">{termsText}</pre>
        </section>
      ) : (
        <div className="cuenta-layout">
          <section className="cuenta-panel cuenta-panel-profile">
            <div className="cuenta-avatar-block">
              <div className="cuenta-avatar-frame">
                {avatarPreview ? (
                  <img alt={`Avatar de ${user.full_name}`} className="cuenta-avatar-image" src={avatarPreview} />
                ) : (
                  <span>{user.full_name.slice(0, 1).toUpperCase()}</span>
                )}
              </div>
              <div className="cuenta-avatar-copy">
                <strong>{user.full_name}</strong>
                <span>{user.branch?.name ?? 'Cuenta participante'}</span>
              </div>
            </div>

            <div className="cuenta-readonly-grid">
              <article>
                <span>Nombre</span>
                <strong>{user.full_name}</strong>
              </article>
              <article>
                <span>Fecha de nacimiento</span>
                <strong>{formatBirthdate(user.birthdate)}</strong>
              </article>
              <article>
                <span>{documentTypeLabel(user.document_type)}</span>
                <strong>{user.cedula}</strong>
              </article>
              <article>
                <span>Documento</span>
                <strong>{documentTypeLabel(user.document_type)}</strong>
              </article>
            </div>
          </section>

          <section className="cuenta-panel">
            <div className="cuenta-panel-head">
              <span className="cuenta-kicker">Campos editables</span>
              <h2>Contacto y foto</h2>
            </div>

            <form className="cuenta-form" onSubmit={handleSubmit}>
              <label className="cuenta-field">
                <span>Correo electronico</span>
                <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} />
              </label>

              <label className="cuenta-field">
                <span>Numero de telefono</span>
                <input type="text" value={phone} onChange={(event) => setPhone(event.target.value)} />
              </label>

              <label className="cuenta-field">
                <span>Sucursal de preferencia</span>
                <select value={branchId} onChange={(event) => setBranchId(event.target.value)} className="cuenta-select">
                  <option value="">- Selecciona una sucursal -</option>
                  {branches.map((branch) => (
                    <option key={branch.id} value={String(branch.id)}>
                      {branch.name}
                    </option>
                  ))}
                </select>
              </label>

              <label className="cuenta-field">
                <span>Fotografia</span>
                <input accept="image/png,image/jpeg,image/webp" type="file" onChange={handleAvatarChange} />
              </label>

              <div className="cuenta-form-actions">
                <button className="cuenta-save-button" disabled={saving} type="submit">
                  {saving ? 'Guardando...' : 'Guardar cambios'}
                </button>
              </div>
            </form>
          </section>
        </div>
      )}
    </section>
  )
}
