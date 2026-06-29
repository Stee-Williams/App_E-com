import { Link } from 'react-router-dom'
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
      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {categories.map((cat: Categorie) => (
          <Link key={cat.id} to={`/produits?categorie=${cat.id}`} className="card-hover p-8">
            <h2 className="text-xl font-bold">{cat.nom}</h2>
            <p className="mt-2 text-muted">{cat.description}</p>
            <span className="link-accent mt-4 inline-block text-sm font-medium">Voir les produits →</span>
          </Link>
        ))}
      </div>
    </div>
  )
}
