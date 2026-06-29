import { Link } from 'react-router-dom'
import { Trash2, X } from 'lucide-react'
import { imageUrl } from '../api/client'
import { formatPrix, prixEffectif } from '../api/types'
import { useCompare } from '../contexts/CompareContext'
import { EmptyState, PageTitle } from '../components/Ui'

const CRITERES = [
  { cle: 'prix', label: 'Prix', valeur: (p: ReturnType<typeof useCompare>['produits'][0]) => formatPrix(prixEffectif(p)) },
  { cle: 'categorie', label: 'Catégorie', valeur: (p: ReturnType<typeof useCompare>['produits'][0]) => p.categorie?.nom ?? '—' },
  { cle: 'stock', label: 'Stock', valeur: (p: ReturnType<typeof useCompare>['produits'][0]) => String(p.stockDisponible ?? p.stock) },
  { cle: 'note', label: 'Note', valeur: (p: ReturnType<typeof useCompare>['produits'][0]) => (p.noteMoyenne != null ? `${p.noteMoyenne}/5` : '—') },
  { cle: 'promo', label: 'Promotion', valeur: (p: ReturnType<typeof useCompare>['produits'][0]) => (p.prixPromo ? 'Oui' : 'Non') },
  { cle: 'description', label: 'Description', valeur: (p: ReturnType<typeof useCompare>['produits'][0]) => p.description?.slice(0, 80) ?? '—' },
] as const

export default function ComparateurPage() {
  const { produits, retirer, vider } = useCompare()

  if (produits.length === 0) {
    return (
      <div>
        <PageTitle title="Comparateur" subtitle="Comparez jusqu'à 4 produits côte à côte" />
        <EmptyState
          message="Aucun produit à comparer."
          action={<Link to="/produits" className="btn-primary">Parcourir le catalogue</Link>}
        />
      </div>
    )
  }

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <PageTitle title="Comparateur" subtitle={`${produits.length} produit(s)`} />
        <button type="button" onClick={vider} className="btn-secondary text-red-600">
          <Trash2 size={16} /> Vider
        </button>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full min-w-[640px] border-collapse text-sm">
          <thead>
            <tr>
              <th className="border-b border-border p-3 text-left font-medium text-muted">Critère</th>
              {produits.map((p) => (
                <th key={p.id} className="border-b border-border p-3 text-left align-top">
                  <div className="relative w-36">
                    <button type="button" onClick={() => retirer(p.id)} className="absolute -right-1 -top-1 rounded-full bg-surface-muted p-1 text-muted hover:text-red-500" aria-label="Retirer">
                      <X size={12} />
                    </button>
                    <Link to={`/produits/${p.id}`}>
                      <img src={imageUrl(p.imagePrincipale, p.id)} alt={p.nom} className="aspect-square w-full rounded-xl object-cover" />
                      <p className="mt-2 font-semibold hover:text-accent">{p.nom}</p>
                    </Link>
                  </div>
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {CRITERES.map((critere) => (
              <tr key={critere.cle} className="border-b border-border">
                <td className="p-3 font-medium text-muted">{critere.label}</td>
                {produits.map((p) => (
                  <td key={p.id} className="p-3 align-top">{critere.valeur(p)}</td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
