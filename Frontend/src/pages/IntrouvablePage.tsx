import { Link } from 'react-router-dom'

export default function IntrouvablePage() {
  return (
    <div className="flex min-h-[50vh] flex-col items-center justify-center text-center">
      <h1 className="text-6xl font-bold text-brand-600">404</h1>
      <p className="mt-4 text-xl">Page introuvable</p>
      <Link to="/" className="btn-primary mt-8">Retour à l'accueil</Link>
    </div>
  )
}
