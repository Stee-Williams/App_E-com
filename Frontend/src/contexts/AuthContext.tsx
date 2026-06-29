import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import { api } from '../api/client'
import type { AuthResponse, Utilisateur } from '../api/types'

interface AuthContextValue {
  utilisateur: Utilisateur | null
  chargement: boolean
  jetonPresent: boolean
  connexion: (email: string, motDePasse: string) => Promise<Utilisateur>
  inscription: (data: { email: string; motDePasse: string; prenom: string; nom: string }) => Promise<void>
  deconnexion: () => void
  rafraichir: () => Promise<void>
  estAdmin: boolean
  estConnecte: boolean
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [utilisateur, setUtilisateur] = useState<Utilisateur | null>(null)
  const [chargement, setChargement] = useState(true)
  const [jetonPresent] = useState(() => !!localStorage.getItem('novashop_token'))

  const rafraichir = useCallback(async () => {
    const token = localStorage.getItem('novashop_token')
    if (!token) {
      setUtilisateur(null)
      return
    }
    try {
      const data = await api<Utilisateur>('/api/auth/me')
      setUtilisateur(data)
    } catch {
      localStorage.removeItem('novashop_token')
      setUtilisateur(null)
    }
  }, [])

  useEffect(() => {
    rafraichir().finally(() => setChargement(false))
  }, [rafraichir])

  const connexion = async (email: string, motDePasse: string): Promise<Utilisateur> => {
    const data = await api<AuthResponse>('/api/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, motDePasse }),
    })
    localStorage.setItem('novashop_token', data.jeton)
    setUtilisateur(data.utilisateur)
    return data.utilisateur
  }

  const inscription = async (payload: { email: string; motDePasse: string; prenom: string; nom: string }) => {
    const data = await api<AuthResponse>('/api/auth/register', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
    localStorage.setItem('novashop_token', data.jeton)
    setUtilisateur(data.utilisateur)
  }

  const deconnexion = () => {
    localStorage.removeItem('novashop_token')
    setUtilisateur(null)
  }

  const value = useMemo(
    () => ({
      utilisateur,
      chargement,
      connexion,
      inscription,
      deconnexion,
      rafraichir,
      jetonPresent,
      estAdmin: utilisateur?.roles?.includes('ROLE_ADMIN') ?? false,
      estConnecte: !!utilisateur,
    }),
    [utilisateur, chargement, jetonPresent, rafraichir],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth doit être utilisé dans AuthProvider')
  return ctx
}
