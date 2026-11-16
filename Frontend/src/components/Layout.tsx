import { Suspense, useState } from 'react'
import { Link, NavLink, Outlet, useSearchParams } from 'react-router-dom'
import { GitCompare, Heart, Menu, Moon, ShoppingBag, Sun, User, X } from 'lucide-react'
import { useAuth } from '../contexts/AuthContext'
import { useCart } from '../contexts/CartContext'
import { useCompare } from '../contexts/CompareContext'
import { useTheme } from '../contexts/ThemeContext'
import { NotificationsBell } from './NotificationsBell'
import { LoadingState } from './Ui'

const liens = [
  { to: '/produits', label: 'Produits' },
  { to: '/categories', label: 'Catégories' },
  { to: '/a-propos', label: 'À propos' },
  { to: '/contact', label: 'Contact' },
]

const liensAdmin = [
  { to: '/administration', onglet: 'stats', label: 'Tableau de bord' },
  { to: '/administration?onglet=commandes', onglet: 'commandes', label: 'Commandes' },
  { to: '/administration?onglet=produits', onglet: 'produits', label: 'Produits / Stock' },
]

export function Header() {
  const { estConnecte, estAdmin, deconnexion } = useAuth()
  const { totalArticles } = useCart()
  const { ids: compareIds } = useCompare()
  const { theme, basculer } = useTheme()
  const [searchParams] = useSearchParams()
  const [menuOuvert, setMenuOuvert] = useState(false)
  const ongletAdminActif = searchParams.get('onglet') ?? 'stats'

  return (
    <header className="sticky top-0 z-50 glass">
      <div className="bg-brand-600 px-4 py-2 text-center text-[9px] font-bold uppercase tracking-[0.12em] text-white sm:text-[10px] sm:tracking-[0.22em]">
        Sport · Mode · Maison · Tech
      </div>
      <div className="mx-auto flex max-w-screen-2xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <Link
          to="/"
          onClick={() => setMenuOuvert(false)}
          className="inline-flex shrink-0 items-center gap-0.5 text-heading"
          aria-label="NovaShop — accueil"
        >
          <span className="font-display text-3xl font-black uppercase leading-none tracking-tight">Nova</span>
          <span className="font-display text-3xl font-black uppercase leading-none tracking-tight">Shop</span>
          <span className="ml-1.5 mt-3 size-2 rounded-full bg-brand-300" aria-hidden="true" />
        </Link>

        <nav className="hidden min-w-0 flex-1 items-center justify-center gap-3 xl:flex 2xl:gap-5" aria-label="Navigation principale">
          {estAdmin ? (
            liensAdmin.map((lien) => {
              const actif = lien.onglet === ongletAdminActif
              return (
                <Link
                  key={lien.onglet}
                  to={lien.to}
                  aria-current={actif ? 'page' : undefined}
                  className={`whitespace-nowrap text-[10px] font-bold uppercase tracking-[0.08em] transition ${actif ? 'text-heading underline decoration-brand-300 decoration-[3px] underline-offset-[10px]' : 'text-muted hover:text-heading'}`}
                >
                  {lien.label}
                </Link>
              )
            })
          ) : (
            <>
              {liens.map((lien) => (
                <NavLink
                  key={lien.to}
                  to={lien.to}
                  className={({ isActive }) =>
                    `whitespace-nowrap text-[10px] font-bold uppercase tracking-[0.08em] transition ${isActive ? 'text-heading underline decoration-brand-300 decoration-[3px] underline-offset-[10px]' : 'text-muted hover:text-heading'}`
                  }
                >
                  {lien.label}
                </NavLink>
              ))}
              {estConnecte && (
                <NavLink
                  to="/commandes"
                  aria-label="Mes commandes"
                  className={({ isActive }) =>
                    `whitespace-nowrap text-[10px] font-bold uppercase tracking-[0.08em] transition ${isActive ? 'text-heading underline decoration-brand-300 decoration-[3px] underline-offset-[10px]' : 'text-muted hover:text-heading'}`
                  }
                >
                  Commandes
                </NavLink>
              )}
              <NavLink
                to="/suivi-commande"
                aria-label="Suivi de commande"
                className={({ isActive }) =>
                  `whitespace-nowrap text-[10px] font-bold uppercase tracking-[0.08em] transition ${isActive ? 'text-heading underline decoration-brand-300 decoration-[3px] underline-offset-[10px]' : 'text-muted hover:text-heading'}`
                }
              >
                Suivi
              </NavLink>
            </>
          )}
        </nav>

        <div className="flex shrink-0 items-center gap-1">
          <button type="button" onClick={basculer} className="rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading" aria-label="Thème">
            {theme === 'dark' ? <Sun size={18} /> : <Moon size={18} />}
          </button>

          {estAdmin ? (
            <>
              <div className="hidden xl:block"><NotificationsBell /></div>
              <Link to="/profil" className="hidden rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading xl:inline-flex" aria-label="Profil">
                <User size={18} />
              </Link>
              <button type="button" onClick={deconnexion} className="btn-secondary hidden px-3 py-2 text-xs xl:inline-flex">
                Déconnexion
              </button>
            </>
          ) : (
            <>
              {estConnecte && (
                <Link to="/liste-souhaits" className="hidden rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading xl:inline-flex" aria-label="Liste de souhaits">
                  <Heart size={18} />
                </Link>
              )}

              <Link to="/panier" className="relative rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading" aria-label="Panier">
                <ShoppingBag size={18} />
                {totalArticles > 0 && (
                  <span className="absolute -right-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-[10px] font-bold tabular-nums text-white">
                    {totalArticles}
                  </span>
                )}
              </Link>

              <Link to="/comparateur" className="relative hidden rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading xl:inline-flex" aria-label="Comparateur">
                <GitCompare size={18} />
                {compareIds.length > 0 && (
                  <span className="absolute -right-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-[10px] font-bold tabular-nums text-white">
                    {compareIds.length}
                  </span>
                )}
              </Link>

              {estConnecte && <div className="hidden xl:block"><NotificationsBell /></div>}

              {estConnecte ? (
                <div className="flex items-center gap-2">
                  <Link to="/profil" className="hidden rounded-xl p-2 text-muted transition hover:bg-surface-muted hover:text-heading xl:inline-flex" aria-label="Profil">
                    <User size={18} />
                  </Link>
                  <button type="button" onClick={deconnexion} className="btn-secondary hidden px-3 py-2 text-xs xl:inline-flex">
                    Déconnexion
                  </button>
                </div>
              ) : (
                <Link to="/connexion" className="btn-primary hidden px-4 py-2.5 text-xs xl:inline-flex">
                  Connexion
                </Link>
              )}
            </>
          )}

          <button
            type="button"
            onClick={() => setMenuOuvert((ouvert) => !ouvert)}
            className="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl text-muted transition hover:bg-surface-muted hover:text-heading focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 xl:hidden"
            aria-label={menuOuvert ? 'Fermer le menu' : 'Ouvrir le menu'}
            aria-expanded={menuOuvert}
            aria-controls="navigation-mobile"
          >
            {menuOuvert ? <X size={20} /> : <Menu size={20} />}
          </button>
        </div>
      </div>

      {menuOuvert && (
        <div id="navigation-mobile" className="border-t border-border bg-page/95 px-4 py-4 shadow-lg shadow-slate-950/5 backdrop-blur-md xl:hidden">
          <nav className="mx-auto flex max-w-screen-2xl flex-col gap-1" aria-label="Navigation principale">
            {estAdmin ? (
              liensAdmin.map((lien) => {
                const actif = lien.onglet === ongletAdminActif
                return (
                  <Link
                    key={lien.onglet}
                    to={lien.to}
                    onClick={() => setMenuOuvert(false)}
                    aria-current={actif ? 'page' : undefined}
                    className={`rounded-lg px-3 py-2.5 text-xs font-bold uppercase tracking-[0.1em] transition ${actif ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300' : 'text-ink hover:bg-surface-muted'}`}
                  >
                    {lien.label}
                  </Link>
                )
              })
            ) : (
              <>
                {liens.map((lien) => (
                  <NavLink
                    key={lien.to}
                    to={lien.to}
                    onClick={() => setMenuOuvert(false)}
                    className={({ isActive }) =>
                      `rounded-lg px-3 py-2.5 text-xs font-bold uppercase tracking-[0.1em] transition ${isActive ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300' : 'text-ink hover:bg-surface-muted'}`
                    }
                  >
                    {lien.label}
                  </NavLink>
                ))}
                {estConnecte && (
                  <NavLink
                    to="/commandes"
                    onClick={() => setMenuOuvert(false)}
                    className={({ isActive }) =>
                      `rounded-lg px-3 py-2.5 text-xs font-bold uppercase tracking-[0.1em] transition ${isActive ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300' : 'text-ink hover:bg-surface-muted'}`
                    }
                  >
                    Mes commandes
                  </NavLink>
                )}
                <NavLink
                  to="/suivi-commande"
                  onClick={() => setMenuOuvert(false)}
                  className={({ isActive }) =>
                    `rounded-lg px-3 py-2.5 text-xs font-bold uppercase tracking-[0.1em] transition ${isActive ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300' : 'text-ink hover:bg-surface-muted'}`
                  }
                >
                  Suivi commande
                </NavLink>
              </>
            )}
          </nav>

          <div className="mx-auto mt-3 flex max-w-screen-2xl flex-wrap items-center gap-2 border-t border-border pt-3">
            {estAdmin ? (
              <>
                <div className="ml-auto"><NotificationsBell /></div>
                <Link
                  to="/profil"
                  onClick={() => setMenuOuvert(false)}
                  className="flex min-h-10 items-center gap-2 rounded-lg px-3 text-xs font-bold uppercase tracking-[0.08em] text-ink hover:bg-surface-muted"
                >
                  <User size={16} /> Profil
                </Link>
                <button type="button" onClick={() => { deconnexion(); setMenuOuvert(false) }} className="btn-secondary px-3 py-2 text-xs">
                  Déconnexion
                </button>
              </>
            ) : (
              <>
                <Link
                  to="/comparateur"
                  onClick={() => setMenuOuvert(false)}
                  className="flex min-h-10 items-center gap-2 rounded-lg px-3 text-xs font-bold uppercase tracking-[0.08em] text-ink hover:bg-surface-muted"
                >
                  <GitCompare size={16} /> Comparer
                </Link>
                {estConnecte && (
                  <>
                    <Link
                      to="/liste-souhaits"
                      onClick={() => setMenuOuvert(false)}
                      className="flex min-h-10 items-center gap-2 rounded-lg px-3 text-xs font-bold uppercase tracking-[0.08em] text-ink hover:bg-surface-muted"
                    >
                      <Heart size={16} /> Souhaits
                    </Link>
                    <div className="ml-auto"><NotificationsBell /></div>
                    <Link
                      to="/profil"
                      onClick={() => setMenuOuvert(false)}
                      className="flex min-h-10 items-center gap-2 rounded-lg px-3 text-xs font-bold uppercase tracking-[0.08em] text-ink hover:bg-surface-muted"
                    >
                      <User size={16} /> Profil
                    </Link>
                    <button type="button" onClick={() => { deconnexion(); setMenuOuvert(false) }} className="btn-secondary px-3 py-2 text-xs">
                      Déconnexion
                    </button>
                  </>
                )}
                {!estConnecte && (
                  <Link to="/connexion" onClick={() => setMenuOuvert(false)} className="btn-primary w-full">
                    Connexion
                  </Link>
                )}
              </>
            )}
          </div>
        </div>
      )}
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
    <footer className="mt-20 bg-[#171917] text-white">
      <div className="mx-auto flex max-w-7xl flex-col items-center gap-6 px-4 py-10 text-center sm:flex-row sm:justify-between sm:px-6 sm:py-12 sm:text-left">
        <Link to="/" className="inline-flex items-center">
          <span className="font-display text-2xl font-black uppercase tracking-tight">NovaShop</span>
          <span className="ml-1.5 mt-2 size-2 rounded-full bg-brand-300" aria-hidden="true" />
        </Link>

        <nav className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
          {liens.map((lien) => (
            <Link
              key={lien.to}
              to={lien.to}
              className="text-[10px] font-bold uppercase tracking-[0.14em] text-white/65 transition hover:text-brand-300"
            >
              {lien.label}
            </Link>
          ))}
        </nav>

        <p className="text-xs text-white/45">
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
      <main className="mx-auto w-full max-w-[1440px] flex-1 px-4 py-7 sm:px-6 lg:px-10">
        <Suspense fallback={<LoadingState />}>
          <Outlet />
        </Suspense>
      </main>
      <Footer />
    </div>
  )
}
