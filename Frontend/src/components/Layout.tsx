import { Link, NavLink, Outlet } from 'react-router-dom'
import { GitCompare, Heart, Moon, ShoppingBag, Sun, User } from 'lucide-react'
import { useAuth } from '../contexts/AuthContext'
import { useCart } from '../contexts/CartContext'
import { useCompare } from '../contexts/CompareContext'
import { useTheme } from '../contexts/ThemeContext'
import { NotificationsBell } from './NotificationsBell'

const liens = [
  { to: '/produits', label: 'Produits' },
  { to: '/categories', label: 'Catégories' },
  { to: '/a-propos', label: 'À propos' },
  { to: '/contact', label: 'Contact' },
]

export function Header() {
  const { estConnecte, estAdmin, deconnexion } = useAuth()
  const { totalArticles } = useCart()
  const { ids: compareIds } = useCompare()
  const { theme, basculer } = useTheme()

  return (
    <header className="sticky top-0 z-50 glass">
      <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4">
        <Link to="/" className="text-xl font-bold text-accent dark:text-brand-400">
          NovaShop
        </Link>

        <nav className="hidden items-center gap-6 md:flex">
          {liens.map((lien) => (
            <NavLink
              key={lien.to}
              to={lien.to}
              className={({ isActive }) =>
                `text-sm font-medium transition ${isActive ? 'text-accent dark:text-brand-400' : 'text-muted hover:text-heading'}`
              }
            >
              {lien.label}
            </NavLink>
          ))}
          {estConnecte && (
            <NavLink
              to="/commandes"
              className={({ isActive }) =>
                `text-sm font-medium transition ${isActive ? 'text-accent dark:text-brand-400' : 'text-muted hover:text-heading'}`
              }
            >
              Mes commandes
            </NavLink>
          )}
          <NavLink
            to="/suivi-commande"
            className={({ isActive }) =>
              `hidden text-sm font-medium transition lg:inline ${isActive ? 'text-accent dark:text-brand-400' : 'text-muted hover:text-heading'}`
            }
          >
            Suivi commande
          </NavLink>
          {estAdmin && (
            <NavLink
              to="/administration"
              className={({ isActive }) =>
                `text-sm font-medium transition ${isActive ? 'text-accent dark:text-brand-400' : 'text-muted hover:text-heading'}`
              }
            >
              Administration
            </NavLink>
          )}
        </nav>

        <div className="flex items-center gap-2">
          <button type="button" onClick={basculer} className="rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading" aria-label="Thème">
            {theme === 'dark' ? <Sun size={18} /> : <Moon size={18} />}
          </button>

          {estConnecte && (
            <Link to="/liste-souhaits" className="rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading" aria-label="Liste de souhaits">
              <Heart size={18} />
            </Link>
          )}

          <Link to="/panier" className="relative rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading" aria-label="Panier">
            <ShoppingBag size={18} />
            {totalArticles > 0 && (
              <span className="absolute -right-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-[10px] font-bold text-white">
                {totalArticles}
              </span>
            )}
          </Link>

          <Link to="/comparateur" className="relative rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading" aria-label="Comparateur">
            <GitCompare size={18} />
            {compareIds.length > 0 && (
              <span className="absolute -right-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-[10px] font-bold text-white">
                {compareIds.length}
              </span>
            )}
          </Link>

          {estConnecte && <NotificationsBell />}

          {estConnecte ? (
            <div className="flex items-center gap-2">
              <Link to="/profil" className="rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading" aria-label="Profil">
                <User size={18} />
              </Link>
              <button type="button" onClick={deconnexion} className="btn-secondary hidden sm:inline-flex">
                Déconnexion
              </button>
            </div>
          ) : (
            <Link to="/connexion" className="btn-primary">
              Connexion
            </Link>
          )}
        </div>
      </div>
    </header>
  )
}

export function Footer() {
  const liens = [
    { to: '/confidentialite', label: 'Confidentialité' },
    { to: '/cgv', label: 'CGV' },
    { to: '/cgi', label: 'CGI' },
    { to: '/contact', label: 'Contact' },
  ]

  return (
    <footer className="mt-auto border-t border-border">
      <div className="mx-auto flex max-w-7xl flex-col items-center gap-6 px-4 py-10 text-center sm:py-12">
        <Link to="/" className="text-sm font-semibold tracking-wide text-accent">
          NovaShop
        </Link>

        <nav className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
          {liens.map((lien) => (
            <Link
              key={lien.to}
              to={lien.to}
              className="text-sm text-muted transition hover:text-heading"
            >
              {lien.label}
            </Link>
          ))}
        </nav>

        <p className="text-xs text-muted/80">
          © {new Date().getFullYear()} NovaShop
        </p>
      </div>
    </footer>
  )
}

export function Layout() {
  return (
    <div className="flex min-h-screen flex-col">
      <Header />
      <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-8">
        <Outlet />
      </main>
      <Footer />
    </div>
  )
}
