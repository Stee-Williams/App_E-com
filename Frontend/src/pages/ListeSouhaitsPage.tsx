import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Heart, Trash2 } from 'lucide-react'
import { api, apiGetCached, imageUrl } from '../api/client'
import { TTL, invaliderCache } from '../api/cache'
import type { ElementListeSouhaits } from '../api/types'
import { formatPrix, prixEffectif } from '../api/types'
import { useAuth } from '../contexts/AuthContext'
import { useCart } from '../contexts/CartContext'
import { EmptyState, ErrorState, LoadingState, PageTitle } from '../components/Ui'

export default function ListeSouhaitsPage() {
  const { estConnecte, jetonPresent, chargement: authChargement } = useAuth()
  const { ajouter } = useCart()
  const [elements, setElements] = useState<ElementListeSouhaits[]>([])
  const [chargement, setChargement] = useState(true)
  const [erreur, setErreur] = useState('')

  useEffect(() => {
    if (!jetonPresent) {
      setChargement(false)
      return
    }
    const ctrl = new AbortController()
    apiGetCached<ElementListeSouhaits[]>('/api/wishlist', TTL.utilisateur, ctrl.signal)
      .then(setElements)
      .catch((e) => {
        if (e.name !== 'AbortError') setErreur(e.message)
      })
      .finally(() => setChargement(false))
    return () => ctrl.abort()
  }, [jetonPresent])

  const supprimer = async (id: number) => {
    await api(`/api/wishlist/${id}`, { method: 'DELETE' })
    invaliderCache('GET:/api/wishlist')
    setElements((prev) => prev.filter((e) => e.id !== id))
  }

  if ((!jetonPresent || !estConnecte) && !authChargement) {
    return (
      <div>
        <PageTitle title="Liste de souhaits" />
        <EmptyState message="Connectez-vous pour voir votre liste de souhaits." action={<Link to="/connexion" className="btn-primary">Connexion</Link>} />
      </div>
    )
  }

  return (
    <div>
      <PageTitle title="Liste de souhaits" />
      {erreur && <ErrorState message={erreur} />}
      {chargement && <LoadingState />}
      {!chargement && elements.length === 0 && <EmptyState message="Votre liste est vide." />}
      <div className="grid gap-4">
        {elements.map((el) => (
          <div key={el.id} className="card-hover flex items-center gap-4 p-4">
            <img src={imageUrl(el.produit.imagePrincipale, el.produit.id)} alt={el.produit.nom} className="h-20 w-20 rounded-xl object-cover" />
            <div className="flex-1">
              <Link to={`/produits/${el.produit.id}`} className="font-semibold hover:text-brand-600 dark:hover:text-brand-400">{el.produit.nom}</Link>
              <p className="text-price text-sm font-medium">{formatPrix(prixEffectif(el.produit))}</p>
            </div>
            <button
              type="button"
              className="btn-primary"
              onClick={() =>
                ajouter({
                  produitId: el.produit.id,
                  nom: el.produit.nom,
                  prix: String(prixEffectif(el.produit)),
                  image: el.produit.imagePrincipale,
                  stock: el.produit.stock,
                })
              }
            >
              <Heart size={14} /> Au panier
            </button>
            <button type="button" onClick={() => supprimer(el.id)} className="text-red-500">
              <Trash2 size={18} />
            </button>
          </div>
        ))}
      </div>
    </div>
  )
}
