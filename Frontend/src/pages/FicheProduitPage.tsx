import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { GitCompare, ShoppingCart, Star } from 'lucide-react'
import { api, apiGet, apiGetCached } from '../api/client'
import { TTL, invaliderCache } from '../api/cache'
import type { Avis, Produit, VarianteProduit } from '../api/types'
import { formatPrix, prixEffectif } from '../api/types'
import { FormField, Select, Textarea } from '../components/Form'
import { ProductCard } from '../components/ProductCard'
import { ProductImage } from '../components/ProductImage'
import { useAuth } from '../contexts/AuthContext'
import { useCart } from '../contexts/CartContext'
import { useCompare } from '../contexts/CompareContext'
import { ErrorState, LoadingState } from '../components/Ui'
import { ajouterRecent } from '../utils/recentlyViewed'

export default function FicheProduitPage() {
  const { id } = useParams()
  const { estConnecte } = useAuth()
  const { ajouter } = useCart()
  const { ajouter: ajouterComparer, contient, plein } = useCompare()
  const [produit, setProduit] = useState<Produit | null>(null)
  const [similaires, setSimilaires] = useState<Produit[]>([])
  const [avis, setAvis] = useState<Avis[]>([])
  const [chargement, setChargement] = useState(true)
  const [erreur, setErreur] = useState('')
  const [quantite, setQuantite] = useState(1)
  const [varianteChoisie, setVarianteChoisie] = useState<VarianteProduit | null>(null)
  const [note, setNote] = useState(5)
  const [commentaire, setCommentaire] = useState('')
  const [messageAvis, setMessageAvis] = useState('')

  useEffect(() => {
    if (!id) return
    setChargement(true)
    setVarianteChoisie(null)
    const ctrl = new AbortController()
    const cheminProduit = `/api/products/${id}`
    const cheminAvis = `/api/products/${id}/reviews`
    const cheminSimilaires = `/api/products/${id}/similar`

    Promise.all([
      apiGetCached<Produit>(cheminProduit, TTL.produits, ctrl.signal),
      apiGetCached<Avis[]>(cheminAvis, TTL.produits, ctrl.signal),
      apiGetCached<Produit[]>(cheminSimilaires, TTL.produits, ctrl.signal),
    ])
      .then(([p, a, s]) => {
        setProduit(p)
        setAvis(a)
        setSimilaires(s)
        ajouterRecent(p.id)
        if (p.variantes?.length) {
          const premiere = p.variantes.find((v) => v.actif !== false && v.stock > 0) ?? p.variantes[0]
          setVarianteChoisie(premiere)
        }
      })
      .catch((e) => {
        if (e.name !== 'AbortError') setErreur(e.message)
      })
      .finally(() => setChargement(false))

    return () => ctrl.abort()
  }, [id])

  if (chargement) return <LoadingState />
  if (erreur || !produit) return <ErrorState message={erreur || 'Produit introuvable'} />

  const prix = prixEffectif(produit)
  const aVariantes = produit.hasVariantes && (produit.variantes?.length ?? 0) > 0
  const stockDispo = aVariantes
    ? (varianteChoisie?.stock ?? 0)
    : (produit.stockDisponible ?? produit.stock)

  const ajouterAuPanier = () => {
    if (aVariantes && !varianteChoisie) return
    ajouter({
      produitId: produit.id,
      varianteId: varianteChoisie?.id,
      libelleVariante: varianteChoisie?.libelle,
      nom: produit.nom,
      prix: String(prix),
      image: produit.imagePrincipale,
      imageSrcSet: produit.imageSrcSet,
      stock: stockDispo,
      quantite,
    })
  }

  const envoyerAvis = async () => {
    try {
      await api(`/api/products/${produit.id}/reviews`, {
        method: 'POST',
        body: JSON.stringify({ note, commentaire }),
      })
      invaliderCache(`GET:/api/products/${produit.id}/reviews`)
      const nouveauxAvis = await apiGet<Avis[]>(`/api/products/${produit.id}/reviews`)
      setAvis(nouveauxAvis)
      setMessageAvis('Merci pour votre avis !')
      setCommentaire('')
    } catch (e) {
      setMessageAvis(e instanceof Error ? e.message : 'Erreur')
    }
  }

  return (
    <div>
      <Link to="/produits" className="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.14em] text-muted transition hover:text-heading">← Retour au catalogue</Link>

      <div className="mt-5 grid gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(360px,0.85fr)] lg:gap-14">
        <div className="relative overflow-hidden rounded-xl bg-surface-muted">
          <span className="absolute left-4 top-4 z-10 rounded-full bg-brand-300 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[0.12em] text-brand-600">
            Nova · sélection
          </span>
          <ProductImage
            path={produit.imagePrincipale}
            imageSrcSet={produit.imageSrcSet}
            produitId={produit.id}
            alt={produit.nom}
            className="aspect-square w-full object-cover"
            sizes="(max-width: 1024px) 100vw, 55vw"
          />
        </div>

        <div className="py-2 lg:py-5">
          {produit.categorie && (
            <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted">{produit.categorie.nom}</p>
          )}
          <h1 className="mt-2 font-display text-5xl font-black uppercase leading-[0.92] tracking-tight sm:text-6xl">{produit.nom}</h1>
          {produit.noteMoyenne != null && (
            <p className="mt-4 flex items-center gap-2 text-xs font-semibold text-muted">
              <Star size={15} fill="currentColor" className="text-brand-600" /> <span className="text-heading">{produit.noteMoyenne}/5</span> · {avis.length} avis
            </p>
          )}
          <p className="mt-5 font-display text-4xl font-bold text-price">{formatPrix(prix)}</p>
          <p className="mt-4 max-w-[58ch] text-sm leading-7 text-muted">{produit.description}</p>
          <p className="mt-4 inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.14em] text-muted">
            <span className={`size-2 rounded-full ${stockDispo > 0 ? 'bg-brand-300' : 'bg-red-500'}`} />
            {stockDispo > 0 ? `${stockDispo} disponible(s)` : 'Rupture de stock'}
          </p>

          {aVariantes && produit.variantes && (
            <FormField label="Variante (taille / couleur)" className="mt-4">
              <Select
                value={varianteChoisie?.id ?? ''}
                onChange={(e) => {
                  const v = produit.variantes?.find((x) => x.id === Number(e.target.value))
                  setVarianteChoisie(v ?? null)
                  setQuantite(1)
                }}
              >
                {produit.variantes
                  .filter((v) => v.actif !== false)
                  .map((v) => (
                    <option key={v.id} value={v.id} disabled={v.stock <= 0}>
                      {v.libelle ?? `${v.taille ?? ''} ${v.couleur ?? ''}`.trim()} — {v.stock > 0 ? `${v.stock} en stock` : 'Rupture'}
                    </option>
                  ))}
              </Select>
            </FormField>
          )}

          <div className="mt-7 flex flex-wrap items-center gap-3">
            <input
              type="number"
              min={1}
              max={stockDispo}
              value={quantite}
              onChange={(e) => setQuantite(Number(e.target.value))}
              className="input-field w-24"
              aria-label="Quantité"
            />
            <button type="button" onClick={ajouterAuPanier} className="btn-primary min-h-11 flex-1 uppercase tracking-[0.08em] sm:flex-none" disabled={stockDispo <= 0}>
              <ShoppingCart size={16} /> Ajouter au panier
            </button>
            <button
              type="button"
              onClick={() => ajouterComparer(produit)}
              className="btn-secondary min-h-11"
              disabled={contient(produit.id) || plein}
              title={plein ? 'Maximum 4 produits' : contient(produit.id) ? 'Déjà dans le comparateur' : 'Comparer'}
            >
              <GitCompare size={16} /> Comparer
            </button>
          </div>
        </div>
      </div>

      {similaires.length > 0 && (
        <section className="mt-16">
          <h2 className="font-display text-4xl font-black uppercase">Tu pourrais aussi aimer</h2>
          <div className="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {similaires.map((p) => (
              <ProductCard key={p.id} produit={p} />
            ))}
          </div>
        </section>
      )}

      <section className="mt-16">
        <h2 className="font-display text-4xl font-black uppercase">Avis clients</h2>
        <div className="mt-4 space-y-4">
          {avis.length === 0 && <p className="text-muted">Aucun avis pour le moment.</p>}
          {avis.map((a) => (
            <div key={a.id} className="card-hover p-4">
              <div className="flex items-center gap-2">
                <span className="font-medium">{a.utilisateur?.prenom} {a.utilisateur?.nom}</span>
                <span className="text-amber-500">{'★'.repeat(a.note)}</span>
              </div>
              {a.commentaire && <p className="mt-2 text-sm text-muted">{a.commentaire}</p>}
            </div>
          ))}
        </div>

        {estConnecte && (
          <div className="mt-8 card-hover p-6">
            <h3 className="font-semibold">Laisser un avis</h3>
            <div className="mt-4 space-y-4">
              <FormField label="Note">
                <Select className="w-40" value={note} onChange={(e) => setNote(Number(e.target.value))}>
                  {[5, 4, 3, 2, 1].map((n) => (
                    <option key={n} value={n}>{n} étoile{n > 1 ? 's' : ''}</option>
                  ))}
                </Select>
              </FormField>
              <FormField label="Commentaire">
                <Textarea
                  placeholder="Partagez votre expérience avec ce produit…"
                  value={commentaire}
                  onChange={(e) => setCommentaire(e.target.value)}
                />
              </FormField>
            </div>
            <button type="button" onClick={envoyerAvis} className="btn-primary mt-4">Publier l'avis</button>
            {messageAvis && <p className="mt-2 text-sm text-brand-600 dark:text-brand-400">{messageAvis}</p>}
          </div>
        )}
      </section>
    </div>
  )
}
