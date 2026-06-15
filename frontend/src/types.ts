export type Role = 'client' | 'cashier' | 'admin'
export type DocumentType = 'cedula' | 'passport' | 'residente'

export interface User {
  id: number
  role: Role
  full_name: string
  cedula: string
  document_type: DocumentType
  email: string
  phone: string | null
  avatar_url?: string | null
  birthdate?: string | null
  resides_in_panama?: boolean
  accepted_terms_at?: string | null
  group_stage_goal_prediction?: number | null
  registration_completed_at?: string | null
  predictions_completed_at?: string | null
  disqualified_at?: string | null
  branch?: UserBranch | null
  wallet?: WalletSummary | null
}

export interface UserBranch {
  id: number
  name: string
  code: string
}

export interface WalletSummary {
  goals_balance: number
  shots_balance: number
  lifetime_goals_earned: number
  lifetime_shots_earned: number
}

export interface WalletMovement {
  id: number
  type: string
  resource_type?: string | null
  resource_id?: number | null
  goals_delta: number
  shots_delta: number
  notes?: string | null
  meta?: Record<string, unknown> | null
  created_at?: string | null
}

export interface InvoicePeriod {
  id: number
  name: string
  slug: string
  stage_order: number
  starts_at: string
  ends_at: string
  exact_score_points: number
  outcome_points: number
}

export interface RegisteredInvoice {
  id: number
  cufe: string
  qr_raw_text: string
  invoice_number?: string | null
  issuer_name?: string | null
  issued_at?: string | null
  purchase_amount: number | string
  points_awarded: number
  validation_status: 'approved' | 'pending' | 'rejected' | string
  validation_notes?: string | null
  daily_points_capped?: boolean
  daily_invoice_limit_hit?: boolean
  created_at?: string
}

export interface ResolvedInvoiceData {
  cufe: string
  invoice_number: string
  purchase_amount: string
  issued_at: string
  issuer_name?: string
}

export interface ClientBootstrap {
  active_phase: InvoicePeriod | null
  phase_goals: number
  general_goals: number
}
