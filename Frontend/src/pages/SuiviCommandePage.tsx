import { useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { Search } from 'lucide-react'
import { apiGet } from '../api/client'
import type { Commande } from '../api/types'
import { STATUTS_COMMANDE, formatPrix } from '../api/types'
import { FormField, Input } from '../components/Form'
import { ErrorState, PageTitle } from '../components/Ui'
import { telechargerFacturePdf } from '../utils/invoice'
import type { Facture } from '../api/types'

export default function SuiviCommandePage() {
  const [searchParams] = useSearchParams()
  const [numero, setNumero] = useState('')
  const [email, setEmail] = useState('')
  const [jeton, setJeton] = useState(searchParams.get('jeton') ?? '')
  const [commande, setCommande] = useState<Commande | null>(null)
  const [erreur, setErreur] = useState('')
  const [chargement, setChargement] = useState(false)

  useEffect(() => {
    const j = searchParams.get('jeton')
    if (j) setJeton(j)
  }, [searchParams])

  const rechercher = async () => {
    setChargement(true)
    setErreur('')
    setCommande(null)
    try {
      const chemin = jeton.trim()
        ? `/api/orders/track?jeton=${encodeURIComponent(jeton.trim())}`
        : `/api/orders/track?numero=${encodeURIComponent(numero.trim())}&email=${encodeURIComponent(email.trim())}`
      const data = await apiGet<Commande>(chemin)
      setCommande(data)
    } catch (e) {
      setErreur(e instanceof Error ? e.message : 'Commande introuvable')
    } finally {
      setChargement(false)
    }
  }

  const telechargerFacture = async () => {
    if (!commande) return
    const chemin = commande.jetonSuivi
      ? `/api/orders/track/invoice?jeton=${encodeURIComponent(commande.jetonSuivi)}`
      : `/api/orders/${commande.id}/invoice`
    const facture = await apiGet<Facture>(chemin)
    telechargerFacturePdf(facture)
  }

  return (
    <div className="mx-auto max-w-xl">
      <PageTitle title="Suivre ma commande" subtitle="Invité ou sans compte" />

      <div className="card-hover space-y-4 p-6">
        <FormField label="Jeton de suivi (reçu après commande)">
          <Input placeholder="Collez votre jeton ici" value={jeton} onChange={(e) => setJeton(e.target.value)} />
        </FormField>
        <p className="text-center text-xs text-muted">— ou —</p>
        <FormField label="N° de commande">
          <Input placeholder="CMD-XXXXXXXX" value={numero} onChange={(e) => setNumero(e.target.value)} />
        </FormField>
        <FormField label="Email utilisé lors de la commande">
          <Input type="email" placeholder="vous@exemple.com" value={email} onChange={(e) => setEmail(e.target.value)} />
        </FormField>

        {erreur && <ErrorState message={erreur} />}
        <button type="button" onClick={rechercher} className="btn-primary w-full" disabled={chargement}>
          <Search size={16} /> {chargement ? 'Recherche...' : 'Rechercher'}
        </button>
      </div>

      {commande && (
        <div className="card-hover mt-6 p-6">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p className="font-bold">{commande.numero}</p>
              <p className="text-sm text-muted">
                {commande.dateCreation && new Date(commande.dateCreation).toLocaleDateString('fr-FR')}
              </p>
              <span className="mt-2 inline-block rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 dark:bg-brand-900/30">
                {STATUTS_COMMANDE[commande.statut] || commande.statut}
              </span>
            </div>
            <p className="text-xl font-bold">{formatPrix(commande.total)}</p>
          </div>
          <ul className="mt-4 space-y-1 text-sm text-muted">
            {commande.lignes?.map((l) => (
              <li key={l.id}>
                {l.quantite}x {l.produit?.nom}
                {l.libelleVariante ? ` (${l.libelleVariante})` : ''} — {formatPrix(l.prixUnitaire)}
              </li>
            ))}
          </ul>
          <button type="button" onClick={telechargerFacture} className="btn-secondary mt-4">
            Télécharger la facture (PDF)
          </button>
        </div>
      )}

      <p className="mt-6 text-center text-sm text-muted">
        <Link to="/connexion" className="link-accent">Connectez-vous</Link> pour voir toutes vos commandes.
      </p>
    </div>
  )
}
