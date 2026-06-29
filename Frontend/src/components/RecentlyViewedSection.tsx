import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { apiGetCached } from '../api/client'
import { TTL } from '../api/cache'
import type { Produit } from '../api/types'
import { ProductCard } from './ProductCard'
import { LoadingState } from './Ui'
import { chargerRecents } from '../utils/recentlyViewed'

export function RecentlyViewedSection({ titre = 'Récemment consultés' }: { titre?: string }) {
  const [produits, setProduits] = useState<Produit[]>([])
  const [chargement, setChargement] = useState(true)

  useEffect(() => {
    chargerRecents((path) => apiGetCached<Produit>(path, TTL.produits))
      .then(setProduits)
      .finally(() => setChargement(false))
  }, [])

  if (chargement) return <LoadingState />
  if (produits.length === 0) return null

  return (
    <section className="mt-16">
      <div className="mb-6 flex items-center justify-between">
        <h2 className="text-2xl font-bold">{titre}</h2>
        <Link to="/produits" className="link-accent text-sm font-medium">Catalogue</Link>
      </div>
      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {produits.map((p) => (
          <ProductCard key={p.id} produit={p} />
        ))}
      </div>
    </section>
  )
}
