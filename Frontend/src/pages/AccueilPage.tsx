import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, ArrowUpRight, ShieldCheck, Sparkles, Truck } from 'lucide-react'
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
  const [erreurProduits, setErreurProduits] = useState('')

  useEffect(() => {
    const ctrl = new AbortController()
    apiGetCached<PaginatedResult<Produit>>('/api/products?page=1&limit=8', TTL.produits, ctrl.signal)
      .then((resultat) => setProduits(resultat.items))
      .catch((error: unknown) => {
        if (!ctrl.signal.aborted) {
          setErreurProduits(error instanceof Error ? error.message : 'Impossible de charger les produits.')
        }
      })
      .finally(() => {
        if (!ctrl.signal.aborted) setChargementProduits(false)
      })
    return () => ctrl.abort()
  }, [])

  const categoriesPopulaires = categories.slice(0, 4)

  return (
    <div>
      <section className="-mx-4 -mt-7 mb-14 overflow-hidden bg-[#171917] text-white sm:-mx-6 lg:-mx-10">
        <div className="relative mx-auto grid min-h-[520px] max-w-[1440px] items-center gap-6 px-6 py-10 md:grid-cols-2 md:px-12 lg:px-20">
          <div className="relative z-10 max-w-xl">
            <p className="mb-5 inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.24em] text-brand-300">
              <span className="size-2 rounded-full bg-brand-300" />
              Nouvelle sélection · NovaShop
            </p>
            <h1 className="font-display text-[4.25rem] font-black uppercase leading-[0.84] tracking-[-0.045em] text-white sm:text-7xl lg:text-8xl">
              Bouge.
              <br />
              Vis.
              <br />
              <span className="text-brand-300">Recommence.</span>
            </h1>
            <p className="mt-6 max-w-md text-sm leading-7 text-white/70 sm:text-base">
              Mode, sport, maison et technologie : trouve les essentiels qui suivent ton rythme, partout.
            </p>
            <div className="mt-8 flex flex-wrap items-center gap-5">
              <Link
                to="/produits"
                className="inline-flex min-h-12 items-center justify-center gap-3 rounded-full bg-brand-300 px-6 text-xs font-extrabold uppercase tracking-[0.1em] text-brand-600 transition hover:bg-brand-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#171917]"
              >
                Découvrir la sélection <ArrowRight size={16} />
              </Link>
              <Link to="/categories" className="text-xs font-bold uppercase tracking-[0.12em] text-white/75 underline decoration-white/35 underline-offset-4 transition hover:text-brand-300">
                Explorer les univers
              </Link>
            </div>
          </div>

          <div className="relative mx-auto flex aspect-square w-full max-w-[540px] items-center justify-center md:ml-auto">
            <div className="absolute inset-[8%] rounded-full bg-brand-300" />
            <div className="absolute inset-[2%] rounded-full border border-white/15" />
            <span className="absolute right-0 top-[12%] z-10 font-display text-sm font-bold uppercase tracking-[0.22em] text-white/65">
              Nova / mouvement
            </span>
            <img
              src="/sneaker-editorial.svg"
              alt=""
              aria-hidden="true"
              className="relative z-10 w-[115%] max-w-none drop-shadow-2xl transition duration-500 ease-out hover:rotate-[-3deg] hover:scale-[1.03]"
            />
            <span className="absolute bottom-[8%] left-[5%] z-10 rounded-full border border-white/25 bg-black/35 px-4 py-2 text-[9px] font-bold uppercase tracking-[0.2em] text-white/80 backdrop-blur">
              Mouvement · quotidien · découverte
            </span>
          </div>
        </div>
      </section>

      <section className="grid gap-3 border-y border-border py-5 sm:grid-cols-3 sm:gap-6" aria-label="Les essentiels NovaShop">
        {[
          { icon: Truck, title: 'Livraison suivie', text: 'Votre commande, étape par étape' },
          { icon: ShieldCheck, title: 'Achats sereins', text: 'Un service à votre écoute' },
          { icon: Sparkles, title: 'Sélection variée', text: 'Des idées pour chaque journée' },
        ].map(({ icon: Icon, title, text }) => (
          <div key={title} className="flex items-center gap-4 px-2 py-2 sm:px-4">
            <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-600">
              <Icon size={20} />
            </span>
            <div>
              <h2 className="font-display text-xl font-bold uppercase leading-none">{title}</h2>
              <p className="mt-1 text-xs text-muted">{text}</p>
            </div>
          </div>
        ))}
      </section>

      <section className="mt-16 sm:mt-20">
        <div className="mb-6 flex items-end justify-between gap-4">
          <div>
            <p className="text-[10px] font-bold uppercase tracking-[0.22em] text-muted">Trouve ton terrain</p>
            <h2 className="mt-1 font-display text-4xl font-black uppercase sm:text-5xl">Explore les univers</h2>
          </div>
          <Link to="/categories" className="hidden items-center gap-1 text-xs font-bold uppercase tracking-[0.1em] text-heading transition hover:text-muted sm:inline-flex">
            Toutes les catégories <ArrowRight size={15} />
          </Link>
        </div>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {categoriesPopulaires.map((cat, index) => (
            <Link
              key={cat.id}
              to={`/produits?categorie=${cat.id}`}
              className="group relative flex min-h-48 flex-col justify-between overflow-hidden rounded-xl border border-border bg-surface p-5 transition duration-200 ease-out hover:-translate-y-1 hover:border-brand-300 hover:bg-brand-50"
            >
              <span className="absolute -right-2 -top-8 font-display text-[8rem] font-black leading-none text-brand-100 transition group-hover:text-brand-200" aria-hidden="true">
                0{index + 1}
              </span>
              <span className="relative z-10 text-[10px] font-bold uppercase tracking-[0.2em] text-muted">Univers 0{index + 1}</span>
              <div className="relative z-10 flex items-end justify-between gap-3">
                <div>
                  <h3 className="font-display text-3xl font-bold uppercase leading-none">{cat.nom}</h3>
                  <p className="mt-2 line-clamp-2 text-xs leading-5 text-muted">{cat.description}</p>
                </div>
                <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white transition group-hover:bg-brand-300 group-hover:text-brand-600">
                  <ArrowUpRight size={18} />
                </span>
              </div>
            </Link>
          ))}
        </div>
      </section>

      <section className="mt-16 sm:mt-20">
        <div className="mb-6 flex items-end justify-between gap-4">
          <div>
            <p className="text-[10px] font-bold uppercase tracking-[0.22em] text-muted">À découvrir maintenant</p>
            <h2 className="mt-1 font-display text-4xl font-black uppercase sm:text-5xl">Sélection du moment</h2>
          </div>
          <Link to="/produits" className="hidden items-center gap-1 text-xs font-bold uppercase tracking-[0.1em] text-heading transition hover:text-muted sm:inline-flex">
            Tous les produits <ArrowRight size={15} />
          </Link>
        </div>
        {erreurProduits ? (
          <p role="alert" className="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            {erreurProduits}
          </p>
        ) : chargementProduits ? (
          <LoadingState />
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
