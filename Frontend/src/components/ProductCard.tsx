import { Link } from 'react-router-dom'
import { GitCompare, Heart, ShoppingCart } from 'lucide-react'
import { formatPrix, prixEffectif, type Produit } from '../api/types'
import { useAuth } from '../contexts/AuthContext'
import { useCart } from '../contexts/CartContext'
import { useCompare } from '../contexts/CompareContext'
import { api } from '../api/client'
import { ProductImage } from './ProductImage'

interface ProductCardProps {
  produit: Produit
}

export function ProductCard({ produit }: ProductCardProps) {
  const { ajouter } = useCart()
  const { estConnecte } = useAuth()
  const { ajouter: ajouterComparer, contient, plein } = useCompare()
  const prix = prixEffectif(produit)
  const enPromo = produit.prixPromo && parseFloat(produit.prixPromo) < parseFloat(produit.prix)
  const aVariantes = produit.hasVariantes
  const stock = produit.stockDisponible ?? produit.stock

  const ajouterAuPanier = () => {
    if (aVariantes) return
    ajouter({
      produitId: produit.id,
      nom: produit.nom,
      prix: String(prixEffectif(produit)),
      image: produit.imagePrincipale,
      imageSrcSet: produit.imageSrcSet,
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
    <article className="card-hover group overflow-hidden">
      <Link to={`/produits/${produit.id}`} className="relative block aspect-[4/5] overflow-hidden bg-surface-muted">
        <ProductImage
          path={produit.imagePrincipale}
          imageSrcSet={produit.imageSrcSet}
          produitId={produit.id}
          alt={produit.nom}
          className="h-full w-full object-cover transition duration-300 ease-out group-hover:scale-[1.035]"
          loading="lazy"
          sizes="(max-width: 640px) 90vw, (max-width: 1024px) 45vw, 25vw"
          pictureClassName="absolute inset-0"
        />
        {enPromo && (
          <span className="absolute left-3 top-3 rounded-full bg-brand-300 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[0.12em] text-brand-600">
            Offre spéciale
          </span>
        )}
      </Link>
      <div className="p-4 sm:p-5">
        {produit.categorie && (
          <p className="text-[10px] font-bold uppercase tracking-[0.18em] text-muted">{produit.categorie.nom}</p>
        )}
        <Link to={`/produits/${produit.id}`}>
          <h3 className="mt-1 font-display text-2xl font-bold uppercase leading-tight tracking-tight transition group-hover:text-accent">
            {produit.nom}
          </h3>
        </Link>
        <div className="mt-2 flex items-center gap-2">
          <span className="text-price text-lg">{formatPrix(prix)}</span>
          {enPromo && <span className="text-xs font-medium text-muted line-through">{formatPrix(produit.prix)}</span>}
        </div>
        {produit.noteMoyenne != null && (
          <p className="mt-2 text-xs font-semibold text-muted">★ <span className="text-heading">{produit.noteMoyenne}/5</span></p>
        )}
        <div className="mt-4 flex gap-2">
          {aVariantes ? (
            <Link to={`/produits/${produit.id}`} className="btn-primary min-h-11 flex-1 text-center text-[10px] uppercase tracking-[0.08em]">
              Choisir une option
            </Link>
          ) : (
            <button type="button" onClick={ajouterAuPanier} className="btn-primary min-h-11 flex-1 text-[10px] uppercase tracking-[0.08em]" disabled={stock <= 0}>
              <ShoppingCart size={15} />
              {stock > 0 ? 'Ajouter' : 'Rupture'}
            </button>
          )}
          <button
            type="button"
            onClick={() => ajouterComparer(produit)}
            className="btn-secondary min-h-11 rounded-full px-3"
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
