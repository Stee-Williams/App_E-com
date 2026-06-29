import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { FileDown } from 'lucide-react'
import { api, apiGet, apiGetCached } from '../api/client'
import { TTL, invaliderCache } from '../api/cache'
import type { Commande, Facture, PaginatedResult } from '../api/types'
import { STATUTS_COMMANDE, formatPrix } from '../api/types'
import { useAuth } from '../contexts/AuthContext'
import { Pagination } from '../components/Pagination'
import { EmptyState, ErrorState, LoadingState, PageTitle } from '../components/Ui'
import { telechargerFacturePdf } from '../utils/invoice'

const LIMIT = 10

export default function CommandesPage() {
  const { estConnecte, jetonPresent, chargement: authChargement } = useAuth()
  const [commandes, setCommandes] = useState<Commande[]>([])
  const [pagination, setPagination] = useState({ page: 1, limit: LIMIT, total: 0, pages: 1 })
  const [page, setPage] = useState(1)
  const [chargement, setChargement] = useState(true)
  const [erreur, setErreur] = useState('')

  const charger = (pageCourante = page, signal?: AbortSignal) => {
    setChargement(true)
    apiGetCached<PaginatedResult<Commande>>(
      `/api/orders/my?page=${pageCourante}&limit=${LIMIT}`,
      TTL.utilisateur,
      signal,
    )
      .then((resultat) => {
        setCommandes(resultat.items)
        setPagination(resultat.pagination)
      })
      .catch((e) => {
        if (e.name !== 'AbortError') setErreur(e.message)
      })
      .finally(() => setChargement(false))
  }

  useEffect(() => {
    if (!jetonPresent) {
      setChargement(false)
      return
    }
    const ctrl = new AbortController()
    charger(page, ctrl.signal)
    return () => ctrl.abort()
  }, [jetonPresent, page])

  const annuler = async (id: number) => {
    await api(`/api/orders/${id}/cancel`, { method: 'POST' })
    invaliderCache('GET:/api/orders/my')
    charger(page)
  }

  const telechargerFacture = async (id: number) => {
    const facture = await apiGet<Facture>(`/api/orders/${id}/invoice`)
    telechargerFacturePdf(facture)
  }

  if ((!jetonPresent || !estConnecte) && !authChargement) {
    return (
      <div>
        <PageTitle title="Mes commandes" />
        <EmptyState message="Connectez-vous pour voir vos commandes." action={<Link to="/connexion" className="btn-primary">Connexion</Link>} />
      </div>
    )
  }

  return (
    <div>
      <PageTitle title="Mes commandes" />
      {erreur && <ErrorState message={erreur} />}
      {chargement && <LoadingState />}
      {!chargement && commandes.length === 0 && <EmptyState message="Aucune commande pour le moment." />}
      <div className="space-y-4">
        {commandes.map((cmd) => (
          <div key={cmd.id} className="card-hover p-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p className="font-bold">{cmd.numero}</p>
                <p className="text-sm text-muted">{cmd.dateCreation && new Date(cmd.dateCreation).toLocaleDateString('fr-FR')}</p>
                <span className="mt-2 inline-block rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 dark:bg-brand-900/30">
                  {STATUTS_COMMANDE[cmd.statut] || cmd.statut}
                </span>
              </div>
              <p className="text-xl font-bold">{formatPrix(cmd.total)}</p>
            </div>
            <ul className="mt-4 space-y-1 text-sm text-muted">
              {cmd.lignes?.map((l) => (
                <li key={l.id}>
                  {l.quantite}x {l.produit?.nom}
                  {l.libelleVariante ? ` (${l.libelleVariante})` : ''} — {formatPrix(l.prixUnitaire)}
                </li>
              ))}
            </ul>
            <div className="mt-4 flex flex-wrap gap-2">
              <button type="button" onClick={() => telechargerFacture(cmd.id)} className="btn-secondary">
                <FileDown size={14} /> Facture PDF
              </button>
              {['en_attente', 'confirmee'].includes(cmd.statut) && (
                <button type="button" onClick={() => annuler(cmd.id)} className="btn-secondary text-red-600">
                  Annuler la commande
                </button>
              )}
            </div>
          </div>
        ))}
      </div>
      {!chargement && commandes.length > 0 && (
        <Pagination pagination={pagination} onPageChange={setPage} />
      )}
    </div>
  )
}
