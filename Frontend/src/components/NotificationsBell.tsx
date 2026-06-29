import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { Bell, Check } from 'lucide-react'
import { useNotifications } from '../contexts/NotificationsContext'

export function NotificationsBell() {
  const { notifications, nonLues, marquerLue, toutMarquerLu } = useNotifications()
  const [ouvert, setOuvert] = useState(false)
  const ref = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const fermer = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOuvert(false)
    }
    document.addEventListener('click', fermer)
    return () => document.removeEventListener('click', fermer)
  }, [])

  return (
    <div className="relative" ref={ref}>
      <button
        type="button"
        onClick={() => setOuvert((v) => !v)}
        className="relative rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading"
        aria-label="Notifications"
      >
        <Bell size={18} />
        {nonLues > 0 && (
          <span className="absolute -right-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
            {nonLues > 9 ? '9+' : nonLues}
          </span>
        )}
      </button>

      {ouvert && (
        <div className="absolute right-0 top-full z-50 mt-2 w-80 rounded-2xl border border-border bg-surface shadow-xl">
          <div className="flex items-center justify-between border-b border-border px-4 py-3">
            <span className="font-semibold">Notifications</span>
            {nonLues > 0 && (
              <button type="button" onClick={toutMarquerLu} className="text-xs text-brand-600 hover:underline">
                Tout marquer lu
              </button>
            )}
          </div>
          <div className="max-h-80 overflow-y-auto">
            {notifications.length === 0 && (
              <p className="p-4 text-center text-sm text-muted">Aucune notification</p>
            )}
            {notifications.map((n) => (
              <div
                key={n.id}
                className={`border-b border-border px-4 py-3 last:border-0 ${n.lu ? 'opacity-70' : 'bg-brand-50/50 dark:bg-brand-900/10'}`}
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0 flex-1">
                    <p className="text-sm font-medium">{n.titre}</p>
                    <p className="mt-0.5 text-xs text-muted">{n.message}</p>
                    {n.lien && (
                      <Link to={n.lien} onClick={() => setOuvert(false)} className="mt-1 inline-block text-xs text-brand-600 hover:underline">
                        Voir
                      </Link>
                    )}
                  </div>
                  {!n.lu && (
                    <button type="button" onClick={() => marquerLue(n.id)} className="shrink-0 text-muted hover:text-brand-600" aria-label="Marquer comme lu">
                      <Check size={14} />
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
