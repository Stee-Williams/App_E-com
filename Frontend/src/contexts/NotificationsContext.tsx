import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import { api } from '../api/client'
import type { Notification, NotificationsResponse } from '../api/types'
import { useAuth } from './AuthContext'

interface NotificationsContextValue {
  notifications: Notification[]
  nonLues: number
  chargement: boolean
  rafraichir: () => Promise<void>
  marquerLue: (id: number) => Promise<void>
  toutMarquerLu: () => Promise<void>
}

const NotificationsContext = createContext<NotificationsContextValue | null>(null)

export function NotificationsProvider({ children }: { children: ReactNode }) {
  const { estConnecte } = useAuth()
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [nonLues, setNonLues] = useState(0)
  const [chargement, setChargement] = useState(false)

  const rafraichir = useCallback(async () => {
    if (!estConnecte) {
      setNotifications([])
      setNonLues(0)
      return
    }
    setChargement(true)
    try {
      const data = await api<NotificationsResponse>('/api/notifications')
      setNotifications(data.items)
      setNonLues(data.nonLues)
    } catch {
      setNotifications([])
      setNonLues(0)
    } finally {
      setChargement(false)
    }
  }, [estConnecte])

  useEffect(() => {
    rafraichir()
    if (!estConnecte) return
    const interval = setInterval(rafraichir, 60_000)
    return () => clearInterval(interval)
  }, [estConnecte, rafraichir])

  const marquerLue = async (id: number) => {
    await api(`/api/notifications/${id}/read`, { method: 'PATCH' })
    setNotifications((prev) => prev.map((n) => (n.id === id ? { ...n, lu: true } : n)))
    setNonLues((prev) => Math.max(0, prev - 1))
  }

  const toutMarquerLu = async () => {
    await api('/api/notifications/read-all', { method: 'PATCH' })
    setNotifications((prev) => prev.map((n) => ({ ...n, lu: true })))
    setNonLues(0)
  }

  const value = useMemo(
    () => ({ notifications, nonLues, chargement, rafraichir, marquerLue, toutMarquerLu }),
    [notifications, nonLues, chargement, rafraichir],
  )

  return <NotificationsContext.Provider value={value}>{children}</NotificationsContext.Provider>
}

export function useNotifications() {
  const ctx = useContext(NotificationsContext)
  if (!ctx) throw new Error('useNotifications doit être utilisé dans NotificationsProvider')
  return ctx
}
