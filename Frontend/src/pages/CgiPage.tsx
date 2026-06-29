import type { ReactNode } from 'react'
import { PageTitle } from '../components/Ui'

function Section({ title, children }: { title: string; children: ReactNode }) {
  return (
    <section className="mt-8">
      <h2 className="text-lg font-semibold text-ink dark:text-slate-100">{title}</h2>
      <div className="mt-3 space-y-3 text-sm leading-relaxed text-muted">{children}</div>
    </section>
  )
}

export default function CgiPage() {
  return (
    <article className="mx-auto max-w-3xl">
      <PageTitle
        title="Conditions Générales d'Utilisation"
        subtitle="Règles d'accès et d'usage du site NovaShop"
      />

      <p className="text-sm text-muted">
        Les présentes Conditions Générales d'Utilisation (CGU) encadrent l'accès et l'utilisation du
        site NovaShop par tout visiteur ou utilisateur inscrit.
      </p>

      <Section title="1. Acceptation">
        <p>
          L'accès au site implique l'acceptation pleine et entière des présentes CGU. Si vous n'acceptez
          pas ces conditions, nous vous invitons à ne pas utiliser le site.
        </p>
      </Section>

      <Section title="2. Accès au site">
        <p>
          NovaShop met tout en œuvre pour assurer un accès continu au site. Toutefois, l'accès peut être
          interrompu pour maintenance, mise à jour ou en cas de force majeure, sans que la responsabilité
          de NovaShop ne puisse être engagée.
        </p>
      </Section>

      <Section title="3. Création de compte">
        <p>
          Certaines fonctionnalités (commandes, liste de souhaits, profil) nécessitent la création d'un
          compte. L'utilisateur s'engage à fournir des informations exactes et à maintenir la
          confidentialité de ses identifiants.
        </p>
        <p>
          NovaShop se réserve le droit de suspendre ou supprimer un compte en cas de violation des
          présentes conditions ou d'usage frauduleux.
        </p>
      </Section>

      <Section title="4. Utilisation autorisée">
        <p>
          Le site est destiné à un usage personnel et non commercial. Il est interdit de :
        </p>
        <ul className="list-disc space-y-1 pl-5">
          <li>tenter d'accéder de manière non autorisée aux systèmes du site ;</li>
          <li>collecter des données d'autres utilisateurs ;</li>
          <li>publier des contenus illicites, diffamatoires ou portant atteinte aux droits de tiers ;</li>
          <li>utiliser des robots ou scripts automatisés sans autorisation préalable.</li>
        </ul>
      </Section>

      <Section title="5. Propriété intellectuelle">
        <p>
          L'ensemble des éléments du site (textes, images, logos, graphismes, structure) est protégé
          par le droit de la propriété intellectuelle. Toute reproduction, représentation ou exploitation
          sans autorisation écrite de NovaShop est interdite.
        </p>
      </Section>

      <Section title="6. Contenus utilisateurs">
        <p>
          Les avis et commentaires publiés par les utilisateurs engagent leur seule responsabilité.
          NovaShop se réserve le droit de modérer, modifier ou supprimer tout contenu contraire à la loi
          ou aux présentes CGU.
        </p>
      </Section>

      <Section title="7. Liens hypertextes">
        <p>
          Le site peut contenir des liens vers des sites tiers. NovaShop n'exerce aucun contrôle sur
          ces sites et décline toute responsabilité quant à leur contenu ou leurs pratiques.
        </p>
      </Section>

      <Section title="8. Protection des données">
        <p>
          Le traitement des données personnelles est décrit dans notre politique de confidentialité.
          En utilisant le site, vous reconnaissez en avoir pris connaissance.
        </p>
      </Section>

      <Section title="9. Limitation de responsabilité">
        <p>
          NovaShop ne garantit pas l'absence d'erreurs ou d'interruptions sur le site. L'utilisateur
          est seul responsable de l'usage qu'il fait des informations et services proposés.
        </p>
      </Section>

      <Section title="10. Modification des CGU">
        <p>
          NovaShop peut modifier les présentes CGU à tout moment. Les utilisateurs seront informés des
          changements substantiels. La poursuite de l'utilisation du site vaut acceptation des nouvelles
          conditions.
        </p>
      </Section>

      <Section title="11. Droit applicable">
        <p>
          Les présentes CGU sont soumises au droit français. En cas de litige, et à défaut de résolution
          amiable, les tribunaux compétents seront saisis conformément aux règles de droit commun.
        </p>
      </Section>

      <p className="mt-10 text-xs text-muted/70">Dernière mise à jour : juin 2026</p>
    </article>
  )
}
