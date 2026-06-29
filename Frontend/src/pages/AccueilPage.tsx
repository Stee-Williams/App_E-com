import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, Sparkles, Truck, Shield } from 'lucide-react'
import { apiGetCached } from '../api/client'
import { TTL } from '../api/cache'
import type { PaginatedResult, Produit } from '../api/types'
import { ProductCard } from '../components/ProductCard'
import { RecentlyViewedSection } from '../components/RecentlyViewedSection'
import { LoadingState } from '../components/Ui'
import { useCategories } from '../contexts/CategoriesContext'

export default function AccueilPage() {
  const { categories } = useCategories()
  const [produits, setProduits] = useState<Produit[]>([])
  const [chargementProduits, setChargementProduits] = useState(true)

  useEffect(() => {
    const ctrl = new AbortController()
    apiGetCached<PaginatedResult<Produit>>('/api/products?page=1&limit=8', TTL.produits, ctrl.signal)
      .then((resultat) => setProduits(resultat.items))
      .finally(() => setChargementProduits(false))
    return () => ctrl.abort()
  }, [])

  const categoriesPopulaires = categories.slice(0, 4)

  return (
    <div>
      <section className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-500 to-indigo-700 px-8 py-16 text-white">
        <div className="relative z-10 max-w-2xl">
          <p className="mb-3 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-sm">
            <Sparkles size={14} /> Nouvelle collection disponible
          </p>
          <h1 className="text-4xl font-bold leading-tight md:text-5xl">
            Découvrez des produits qui vous ressemblent
          </h1>
          <p className="mt-4 text-lg text-indigo-100">
            Mode, tech, maison et sport — livraison rapide et retours simplifiés.
          </p>
          <Link to="/produits" className="btn-primary mt-8 bg-white/95 text-brand-600 hover:bg-white">
            Voir les produits <ArrowRight size={16} />
          </Link>
        </div>
      </section>

      <section className="mt-12 grid gap-4 md:grid-cols-3">
        {[
          { icon: Truck, title: 'Livraison rapide', text: 'Expédition sous 48h' },
          { icon: Shield, title: 'Paiement sécurisé', text: 'Transactions protégées' },
          { icon: Sparkles, title: 'Qualité garantie', text: 'Produits sélectionnés' },
        ].map(({ icon: Icon, title, text }) => (
          <div key={title} className="card-hover p-6">
            <Icon className="text-accent" size={24} />
            <h3 className="mt-3 font-semibold">{title}</h3>
            <p className="mt-1 text-sm text-muted">{text}</p>
          </div>
        ))}
      </section>

      <section className="mt-16">
        <div className="mb-6 flex items-center justify-between">
          <h2 className="text-2xl font-bold">Catégories populaires</h2>
          <Link to="/categories" className="link-accent text-sm font-medium">Tout voir</Link>
        </div>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {categoriesPopulaires.map((cat) => (
            <Link key={cat.id} to={`/produits?categorie=${cat.id}`} className="card-hover p-6 text-center">
              <h3 className="font-semibold">{cat.nom}</h3>
              <p className="mt-1 text-sm text-muted line-clamp-2">{cat.description}</p>
            </Link>
          ))}
        </div>
      </section>

      <section className="mt-16">
        <div className="mb-6 flex items-center justify-between">
          <h2 className="text-2xl font-bold">Produits vedettes</h2>
          <Link to="/produits" className="link-accent text-sm font-medium">Catalogue complet</Link>
        </div>
        {chargementProduits ? (
          <LoadingState />
        ) : (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {produits.map((p) => (
              <ProductCard key={p.id} produit={p} />
            ))}
          </div>
        )}
      </section>

      <RecentlyViewedSection />
    </div>
  )
}
