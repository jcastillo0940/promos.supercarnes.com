import { useEffect, useId, useRef, useState } from 'react'

export function InfoTooltip({
  content,
  label = 'Mas informacion',
  compact = false,
}: {
  content: string
  label?: string
  compact?: boolean
}) {
  const [open, setOpen] = useState(false)
  const wrapperRef = useRef<HTMLSpanElement | null>(null)
  const tooltipId = useId()

  useEffect(() => {
    if (!open) return

    function handlePointerDown(event: MouseEvent | TouchEvent) {
      if (!wrapperRef.current?.contains(event.target as Node)) {
        setOpen(false)
      }
    }

    function handleEscape(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        setOpen(false)
      }
    }

    document.addEventListener('mousedown', handlePointerDown)
    document.addEventListener('touchstart', handlePointerDown)
    document.addEventListener('keydown', handleEscape)

    return () => {
      document.removeEventListener('mousedown', handlePointerDown)
      document.removeEventListener('touchstart', handlePointerDown)
      document.removeEventListener('keydown', handleEscape)
    }
  }, [open])

  return (
    <span
      ref={wrapperRef}
      className={compact ? 'info-tooltip compact' : 'info-tooltip'}
      onMouseEnter={() => setOpen(true)}
      onMouseLeave={() => setOpen(false)}
    >
      <button
        type="button"
        className="info-tooltip-trigger"
        aria-label={label}
        aria-describedby={open ? tooltipId : undefined}
        aria-expanded={open}
        onClick={() => setOpen((current) => !current)}
        onBlur={(event) => {
          if (!wrapperRef.current?.contains(event.relatedTarget as Node | null)) {
            setOpen(false)
          }
        }}
      >
        <span className="material-symbols-outlined">info</span>
      </button>

      {open ? (
        <span id={tooltipId} role="tooltip" className="info-tooltip-bubble">
          {content}
        </span>
      ) : null}
    </span>
  )
}
