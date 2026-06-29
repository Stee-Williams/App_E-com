import { BrowserRouter, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './contexts/AuthContext'
import { CartProvider } from './contexts/CartContext'
import { CategoriesProvider } from './contexts/CategoriesContext'
import { CompareProvider } from './contexts/CompareContext'
import { NotificationsProvider } from './contexts/NotificationsContext'
import { ThemeProvider } from './contexts/ThemeContext'
import { Layout } from './components/Layout'
import AccueilPage from './pages/AccueilPage'
import ProduitsPage from './pages/ProduitsPage'
import FicheProduitPage from './pages/FicheProduitPage'
import CategoriesPage from './pages/CategoriesPage'
import PanierPage from './pages/PanierPage'
import ListeSouhaitsPage from './pages/ListeSouhaitsPage'
import CommandesPage from './pages/CommandesPage'
import ComparateurPage from './pages/ComparateurPage'
import SuiviCommandePage from './pages/SuiviCommandePage'
import ConnexionPage from './pages/ConnexionPage'
import InscriptionPage from './pages/InscriptionPage'
import MotDePasseOubliePage from './pages/MotDePasseOubliePage'
import ProfilPage from './pages/ProfilPage'
import AdministrationPage from './pages/AdministrationPage'
import AProposPage from './pages/AProposPage'
import ContactPage from './pages/ContactPage'
import ConfidentialitePage from './pages/ConfidentialitePage'
import CgvPage from './pages/CgvPage'
import CgiPage from './pages/CgiPage'
import IntrouvablePage from './pages/IntrouvablePage'

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
