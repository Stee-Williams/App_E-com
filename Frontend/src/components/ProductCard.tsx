import { Link } from 'react-router-dom'
import { GitCompare, Heart, ShoppingCart } from 'lucide-react'
import { imageUrl } from '../api/client'
import { formatPrix, prixEffectif, type Produit } from '../api/types'
import { useAuth } from '../contexts/AuthContext'
import { useCart } from '../contexts/CartContext'
import { useCompare } from '../contexts/CompareContext'
import { api } from '../api/client'

interface ProductCardProps {
  produit: Produit
}

export function ProductCard({ produit }: ProductCardProps) {
  const { ajouter } = useCart()
  const { estConnecte } = useAuth()
  const { ajouter: ajouterComparer, contient, plein } = useCompare()
  const prix = prixEffectif(produit)
  const enPromo = produit.prixPromo && parseFloat(produit.prixPromo) < parseFloat(produit.prix)
  const aVariantes = produit.hasVariantes && (produit.variantes?.length ?? 0) > 0
  const stock = produit.stockDisponible ?? produit.stock

  const ajouterAuPanier = () => {
    if (aVariantes) return
    ajouter({
      produitId: produit.id,
      nom: produit.nom,
      prix: String(prixEffectif(produit)),
      image: produit.imagePrincipale,
      stock,
    })
  }

  const ajouterListeSouhaits = async () => {
    if (!estConnecte) return
    await api('/api/wishlist', {
      method: 'POST',
      body: JSON.stringify({ produitId: produit.id }),
    })
  }

  return (
    <article className="card-hover overflow-hidden">
      <Link to={`/produits/${produit.id}`} className="block aspect-square overflow-hidden bg-surface-inset">
        <img src={imageUrl(produit.imagePrincipale, produit.id)} alt={produit.nom} className="h-full w-full object-cover transition hover:scale-105" loading="lazy" />
      </Link>
      <div className="p-4">
        {produit.categorie && (
          <p className="text-xs font-medium uppercase tracking-wide text-accent">{produit.categorie.nom}</p>
        )}
        <Link to={`/produits/${produit.id}`}>
          <h3 className="mt-1 font-semibold hover:text-accent">{produit.nom}</h3>
        </Link>
        <div className="mt-2 flex items-center gap-2">
          <span className="text-lg font-bold text-price">{formatPrix(prix)}</span>
          {enPromo && <span className="text-sm text-muted line-through">{formatPrix(produit.prix)}</span>}
        </div>
        {produit.noteMoyenne != null && (
          <p className="mt-1 text-xs text-amber-500">★ {produit.noteMoyenne}/5</p>
        )}
        <div className="mt-4 flex gap-2">
          {aVariantes ? (
            <Link to={`/produits/${produit.id}`} className="btn-primary flex-1 text-center text-xs">
              Choisir variante
            </Link>
          ) : (
            <button type="button" onClick={ajouterAuPanier} className="btn-primary flex-1 text-xs" disabled={stock <= 0}>
              <ShoppingCart size={14} />
              {stock > 0 ? 'Ajouter' : 'Rupture'}
            </button>
          )}
          <button
            type="button"
            onClick={() => ajouterComparer(produit)}
            className="btn-secondary px-3"
            disabled={contient(produit.id) || plein}
            aria-label="Comparer"
            title="Comparer"
          >
            <GitCompare size={14} />
          </button>
          {estConnecte && (
            <button type="button" onClick={ajouterListeSouhaits} className="btn-secondary px-3" aria-label="Liste de souhaits">
              <Heart size={14} />
            </button>
          )}
        </div>
      </div>
    </article>
  )
}
