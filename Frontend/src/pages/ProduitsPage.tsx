import { useCallback, useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { RotateCcw, Search } from 'lucide-react'
import { apiGetCached } from '../api/client'
import { TTL } from '../api/cache'
import type { PaginatedResult, Produit } from '../api/types'
import { ProductCard } from '../components/ProductCard'
import { Pagination } from '../components/Pagination'
import { FormField, Input, Select } from '../components/Form'
import { EmptyState, ErrorState, LoadingState, PageTitle } from '../components/Ui'
import { useCategories } from '../contexts/CategoriesContext'

const OPTIONS_TRI = [
  { value: 'recent', label: 'Plus récents' },
  { value: 'prix_asc', label: 'Prix croissant' },
  { value: 'prix_desc', label: 'Prix décroissant' },
  { value: 'nom_asc', label: 'Nom A → Z' },
  { value: 'nom_desc', label: 'Nom Z → A' },
] as const

function lireFiltres(params: URLSearchParams) {
  return {
    q: params.get('q') || '',
    categorie: params.get('categorie') || '',
    tri: params.get('tri') || 'recent',
    prixMin: params.get('prixMin') || '',
    prixMax: params.get('prixMax') || '',
    promo: params.get('promo') === '1',
    enStock: params.get('enStock') === '1',
    page: Math.max(1, parseInt(params.get('page') || '1', 10) || 1),
  }
}

function filtresActifs(f: ReturnType<typeof lireFiltres>) {
  return Boolean(
    f.q || f.categorie || f.prixMin || f.prixMax || f.promo || f.enStock || (f.tri && f.tri !== 'recent')
  )
}

export default function ProduitsPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const filtres = lireFiltres(searchParams)

  const [produits, setProduits] = useState<Produit[]>([])
  const [pagination, setPagination] = useState({ page: 1, limit: 12, total: 0, pages: 1 })
  const { categories } = useCategories()
  const [chargement, setChargement] = useState(true)
  const [erreur, setErreur] = useState('')
  const [rechercheLocale, setRechercheLocale] = useState(filtres.q)
  const [prixMinLocal, setPrixMinLocal] = useState(filtres.prixMin)
  const [prixMaxLocal, setPrixMaxLocal] = useState(filtres.prixMax)

  useEffect(() => {
    setRechercheLocale(filtres.q)
    setPrixMinLocal(filtres.prixMin)
    setPrixMaxLocal(filtres.prixMax)
  }, [filtres.q, filtres.prixMin, filtres.prixMax])

  const mettreAJour = useCallback((maj: Partial<ReturnType<typeof lireFiltres>>) => {
    const next = new URLSearchParams(searchParams)
    const fusion = { ...filtres, ...maj }
    const changementFiltre = Object.keys(maj).some((cle) => cle !== 'page')

    const definir = (cle: string, valeur: string) => {
      if (valeur) next.set(cle, valeur)
      else next.delete(cle)
    }

    definir('q', fusion.q.trim())
    definir('categorie', fusion.categorie)
    definir('tri', fusion.tri !== 'recent' ? fusion.tri : '')
    definir('prixMin', fusion.prixMin)
    definir('prixMax', fusion.prixMax)

    if (fusion.promo) next.set('promo', '1')
    else next.delete('promo')

    if (fusion.enStock) next.set('enStock', '1')
    else next.delete('enStock')

    if (changementFiltre) {
      next.delete('page')
    } else if (fusion.page > 1) {
      next.set('page', String(fusion.page))
    } else {
      next.delete('page')
    }

    setSearchParams(next, { replace: true })
  }, [filtres, searchParams, setSearchParams])

  const lancerRecherche = () => mettreAJour({ q: rechercheLocale })

  const reinitialiser = () => {
    setRechercheLocale('')
    setSearchParams({}, { replace: true })
  }

  useEffect(() => {
    setChargement(true)
    setErreur('')

    const params = new URLSearchParams()
    if (filtres.q) params.set('q', filtres.q)
    if (filtres.categorie) params.set('categorie', filtres.categorie)
    if (filtres.tri && filtres.tri !== 'recent') params.set('tri', filtres.tri)
    if (filtres.prixMin) params.set('prixMin', filtres.prixMin)
    if (filtres.prixMax) params.set('prixMax', filtres.prixMax)
    if (filtres.promo) params.set('promo', '1')
    if (filtres.enStock) params.set('enStock', '1')
    if (filtres.page > 1) params.set('page', String(filtres.page))
    params.set('limit', '12')

    const chemin = `/api/products?${params}`
    const ctrl = new AbortController()

    apiGetCached<PaginatedResult<Produit>>(chemin, TTL.produits, ctrl.signal)
      .then((resultat) => {
        setProduits(resultat.items)
        setPagination(resultat.pagination)
      })
      .catch((e) => {
        if (e.name !== 'AbortError') setErreur(e.message)
      })
      .finally(() => setChargement(false))

    return () => ctrl.abort()
  }, [filtres.q, filtres.categorie, filtres.tri, filtres.prixMin, filtres.prixMax, filtres.promo, filtres.enStock, filtres.page])

  const afficherFiltresAvances = Boolean(filtres.q) || filtresActifs(filtres)

  return (
    <div>
      <PageTitle title="Catalogue produits" subtitle="Parcourez notre sélection complète" />

      <div className="surface-panel mb-6 space-y-4">
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_11rem_11rem] xl:items-end">
          <FormField label="Recherche" className="sm:col-span-2 xl:col-span-1">
            <div className="flex gap-2">
              <div className="relative min-w-0 flex-1">
                <Search className="pointer-events-none absolute left-3 top-1/2 size-[18px] -translate-y-1/2 text-muted" />
                <Input
                  className="pl-10"
                  placeholder="Nom, description ou catégorie…"
                  value={rechercheLocale}
                  onChange={(e) => setRechercheLocale(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter') lancerRecherche()
                  }}
                />
              </div>
              <button
                type="button"
                onClick={lancerRecherche}
                className="btn-primary h-10 shrink-0 px-4"
              >
                Rechercher
              </button>
            </div>
          </FormField>

          <FormField label="Catégorie">
            <Select
              value={filtres.categorie}
              onChange={(e) => mettreAJour({ categorie: e.target.value })}
            >
              <option value="">Toutes</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>{c.nom}</option>
              ))}
            </Select>
          </FormField>

          <FormField label="Trier par">
            <Select
              value={filtres.tri}
              onChange={(e) => mettreAJour({ tri: e.target.value })}
            >
              {OPTIONS_TRI.map((o) => (
                <option key={o.value} value={o.value}>{o.label}</option>
              ))}
            </Select>
          </FormField>
        </div>

        {afficherFiltresAvances && (
          <div className="grid grid-cols-1 gap-3 border-t border-border pt-4 sm:grid-cols-2 lg:grid-cols-[9rem_9rem_1fr_auto] lg:items-end">
            <FormField label="Prix min (FCFA)">
              <Input
                type="number"
                min={0}
                placeholder="0"
                value={prixMinLocal}
                onChange={(e) => setPrixMinLocal(e.target.value)}
                onBlur={() => {
                  if (prixMinLocal !== filtres.prixMin) mettreAJour({ prixMin: prixMinLocal })
                }}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') mettreAJour({ prixMin: prixMinLocal })
                }}
              />
            </FormField>
            <FormField label="Prix max (FCFA)">
              <Input
                type="number"
                min={0}
                placeholder="500 000"
                value={prixMaxLocal}
                onChange={(e) => setPrixMaxLocal(e.target.value)}
                onBlur={() => {
                  if (prixMaxLocal !== filtres.prixMax) mettreAJour({ prixMax: prixMaxLocal })
                }}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') mettreAJour({ prixMax: prixMaxLocal })
                }}
              />
            </FormField>

            <div className="flex flex-wrap items-center gap-4 sm:col-span-2 lg:col-span-1 lg:pb-0.5">
              <label className="flex cursor-pointer items-center gap-2 text-sm text-ink">
                <input
                  type="checkbox"
                  className="rounded border-border text-brand-600 focus:ring-brand-500"
                  checked={filtres.promo}
                  onChange={(e) => mettreAJour({ promo: e.target.checked })}
                />
                En promotion
              </label>
              <label className="flex cursor-pointer items-center gap-2 text-sm text-ink">
                <input
                  type="checkbox"
                  className="rounded border-border text-brand-600 focus:ring-brand-500"
                  checked={filtres.enStock}
                  onChange={(e) => mettreAJour({ enStock: e.target.checked })}
                />
                En stock uniquement
              </label>
            </div>

            {filtresActifs(filtres) && (
              <button type="button" onClick={reinitialiser} className="btn-secondary h-10 justify-self-start lg:justify-self-end">
                <RotateCcw size={16} />
                Réinitialiser
              </button>
            )}
          </div>
        )}
      </div>

      {!chargement && !erreur && (
        <p className="mb-4 text-sm text-muted">
          {pagination.total === 0
            ? filtres.q
              ? `Aucun résultat pour « ${filtres.q} »`
              : 'Aucun produit ne correspond aux filtres'
            : `${pagination.total} produit${pagination.total > 1 ? 's' : ''} trouvé${pagination.total > 1 ? 's' : ''}${filtres.q ? ` pour « ${filtres.q} »` : ''}`}
        </p>
      )}

      {erreur && <ErrorState message={erreur} />}
      {chargement && <LoadingState />}
      {!chargement && !erreur && produits.length === 0 && (
        <EmptyState
          message={filtres.q ? 'Essayez un autre terme ou élargissez les filtres.' : 'Aucun produit trouvé.'}
          action={filtresActifs(filtres) ? (
            <button type="button" onClick={reinitialiser} className="btn-secondary">
              Effacer les filtres
            </button>
          ) : undefined}
        />
      )}
      {!chargement && produits.length > 0 && (
        <>
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {produits.map((p) => (
              <ProductCard key={p.id} produit={p} />
            ))}
          </div>
          <Pagination pagination={pagination} onPageChange={(page) => mettreAJour({ page })} />
        </>
      )}
    </div>
  )
}
