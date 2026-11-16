import { Link } from 'react-router-dom'
import { ArrowUpRight } from 'lucide-react'
import type { Categorie } from '../api/types'
import { EmptyState, LoadingState, PageTitle } from '../components/Ui'
import { useCategories } from '../contexts/CategoriesContext'

export default function CategoriesPage() {
  const { categories, chargement } = useCategories()

  return (
    <div>
      <PageTitle title="Catégories" subtitle="Explorez nos univers" />
      {chargement && <LoadingState />}
      {!chargement && categories.length === 0 && <EmptyState message="Aucune catégorie disponible." />}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {categories.map((cat: Categorie, index) => (
          <Link key={cat.id} to={`/produits?categorie=${cat.id}`} className="card-hover group relative flex min-h-56 flex-col justify-between overflow-hidden p-6 sm:p-8">
            <span className="absolute -right-2 -top-8 font-display text-[9rem] font-black leading-none text-brand-100 transition group-hover:text-brand-200" aria-hidden="true">
              {String(index + 1).padStart(2, '0')}
            </span>
            <span className="relative z-10 text-[10px] font-bold uppercase tracking-[0.2em] text-muted">Univers {String(index + 1).padStart(2, '0')}</span>
            <div className="relative z-10 flex items-end justify-between gap-4">
              <div>
                <h2 className="font-display text-4xl font-bold uppercase leading-none">{cat.nom}</h2>
                <p className="mt-2 max-w-[36ch] text-sm text-muted">{cat.description}</p>
                <span className="mt-4 inline-block text-[10px] font-bold uppercase tracking-[0.12em] text-heading">Voir les produits</span>
              </div>
              <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white transition group-hover:bg-brand-300 group-hover:text-brand-600">
                <ArrowUpRight size={20} />
              </span>
            </div>
          </Link>
        ))}
      </div>
    </div>
  )
}
