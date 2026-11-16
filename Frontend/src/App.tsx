import { lazy } from 'react'
import { BrowserRouter, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext'
import { CartProvider } from './contexts/CartContext'
import { CategoriesProvider } from './contexts/CategoriesContext'
import { CompareProvider } from './contexts/CompareContext'
import { NotificationsProvider } from './contexts/NotificationsContext'
import { ThemeProvider } from './contexts/ThemeContext'
import { Layout } from './components/Layout'

const AccueilPage = lazy(() => import('./pages/AccueilPage'))
const ProduitsPage = lazy(() => import('./pages/ProduitsPage'))
const FicheProduitPage = lazy(() => import('./pages/FicheProduitPage'))
const CategoriesPage = lazy(() => import('./pages/CategoriesPage'))
const PanierPage = lazy(() => import('./pages/PanierPage'))
const ListeSouhaitsPage = lazy(() => import('./pages/ListeSouhaitsPage'))
const CommandesPage = lazy(() => import('./pages/CommandesPage'))
const ComparateurPage = lazy(() => import('./pages/ComparateurPage'))
const SuiviCommandePage = lazy(() => import('./pages/SuiviCommandePage'))
const ConnexionPage = lazy(() => import('./pages/ConnexionPage'))
const InscriptionPage = lazy(() => import('./pages/InscriptionPage'))
const MotDePasseOubliePage = lazy(() => import('./pages/MotDePasseOubliePage'))
const ProfilPage = lazy(() => import('./pages/ProfilPage'))
const AdministrationPage = lazy(() => import('./pages/AdministrationPage'))
const AProposPage = lazy(() => import('./pages/AProposPage'))
const ContactPage = lazy(() => import('./pages/ContactPage'))
const ConfidentialitePage = lazy(() => import('./pages/ConfidentialitePage'))
const CgvPage = lazy(() => import('./pages/CgvPage'))
const CgiPage = lazy(() => import('./pages/CgiPage'))
const PaiementPage = lazy(() => import('./pages/PaiementPage'))
const IntrouvablePage = lazy(() => import('./pages/IntrouvablePage'))

export default function App() {
  return (
    <ThemeProvider>
      <AuthProvider>
        <NotificationsProvider>
          <CompareProvider>
            <CategoriesProvider>
              <CartProvider>
                <BrowserRouter>
                  <Routes>
                    <Route element={<Layout />}>
                      <Route index element={<AccueilPage />} />
                      <Route path="produits" element={<ProduitsPage />} />
                      <Route path="produits/:id" element={<FicheProduitPage />} />
                      <Route path="categories" element={<CategoriesPage />} />
                      <Route path="panier" element={<PanierPage />} />
                      <Route path="paiement/retour" element={<PaiementPage />} />
                      <Route path="paiement/annule" element={<PaiementPage />} />
                      <Route path="liste-souhaits" element={<ListeSouhaitsPage />} />
                      <Route path="commandes" element={<CommandesPage />} />
                      <Route path="comparateur" element={<ComparateurPage />} />
                      <Route path="suivi-commande" element={<SuiviCommandePage />} />
                      <Route path="connexion" element={<ConnexionPage />} />
                      <Route path="inscription" element={<InscriptionPage />} />
                      <Route path="mot-de-passe-oublie" element={<MotDePasseOubliePage />} />
                      <Route path="profil" element={<ProfilPage />} />
                      <Route path="administration" element={<AdministrationPage />} />
                      <Route path="a-propos" element={<AProposPage />} />
                      <Route path="contact" element={<ContactPage />} />
                      <Route path="confidentialite" element={<ConfidentialitePage />} />
                      <Route path="cgv" element={<CgvPage />} />
                      <Route path="cgi" element={<CgiPage />} />
                      <Route path="*" element={<IntrouvablePage />} />
                    </Route>
                  </Routes>
                </BrowserRouter>
              </CartProvider>
            </CategoriesProvider>
          </CompareProvider>
        </NotificationsProvider>
      </AuthProvider>
    </ThemeProvider>
  )
}
