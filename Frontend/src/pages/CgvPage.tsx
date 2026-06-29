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

export default function CgvPage() {
  return (
    <article className="mx-auto max-w-3xl">
      <PageTitle
        title="Conditions Générales de Vente"
        subtitle="Applicables à toute commande passée sur NovaShop"
      />

      <p className="text-sm text-muted">
        Les présentes Conditions Générales de Vente (CGV) régissent les relations contractuelles entre
        NovaShop et tout client particulier ou professionnel effectuant un achat sur le site.
      </p>

      <Section title="1. Objet et champ d'application">
        <p>
          Les CGV définissent les droits et obligations des parties dans le cadre de la vente en ligne
          des produits proposés sur NovaShop. Toute commande implique l'acceptation sans réserve des
          présentes conditions.
        </p>
      </Section>

      <Section title="2. Produits et prix">
        <p>
          Les produits sont décrits avec la plus grande exactitude possible. Les photographies n'ont pas
          de valeur contractuelle. Les prix sont indiqués en euros, toutes taxes comprises (TTC), hors
          frais de livraison affichés avant validation de la commande.
        </p>
        <p>
          NovaShop se réserve le droit de modifier ses prix à tout moment. Le prix facturé est celui
          en vigueur au moment de la validation de la commande.
        </p>
      </Section>

      <Section title="3. Commande">
        <p>
          Le client sélectionne les produits, les ajoute au panier et valide sa commande après avoir
          vérifié le récapitulatif. La confirmation de commande est envoyée par email à l'adresse
          renseignée lors de l'inscription.
        </p>
        <p>
          NovaShop se réserve le droit d'annuler ou de refuser toute commande en cas de litige
          antérieur, d'informations erronées ou de stock insuffisant.
        </p>
      </Section>

      <Section title="4. Paiement">
        <p>
          Le règlement s'effectue selon les moyens de paiement proposés lors du passage de commande.
          La commande n'est définitive qu'après confirmation du paiement par l'organisme bancaire
          ou le prestataire de paiement.
        </p>
      </Section>

      <Section title="5. Livraison">
        <p>
          Les délais de livraison sont communiqués à titre indicatif lors de la commande. NovaShop
          s'engage à expédier les produits dans les meilleurs délais après confirmation du paiement.
        </p>
        <p>
          Le risque de perte ou d'endommagement des produits est transféré au client au moment où
          celui-ci prend physiquement possession des biens.
        </p>
      </Section>

      <Section title="6. Droit de rétractation">
        <p>
          Conformément à la réglementation en vigueur, le client dispose d'un délai de 14 jours à
          compter de la réception des produits pour exercer son droit de rétractation, sans avoir à
          justifier de motifs ni à payer de pénalités.
        </p>
        <p>
          Les produits doivent être retournés dans leur état d'origine, complets et dans leur
          emballage. Les frais de retour sont à la charge du client, sauf disposition contraire.
        </p>
      </Section>

      <Section title="7. Garanties">
        <p>
          Tous les produits bénéficient de la garantie légale de conformité et de la garantie contre
          les vices cachés, dans les conditions prévues par la loi.
        </p>
      </Section>

      <Section title="8. Responsabilité">
        <p>
          La responsabilité de NovaShop ne saurait être engagée en cas de force majeure, de
          dysfonctionnement du réseau internet ou d'utilisation frauduleuse du site par un tiers.
        </p>
      </Section>

      <Section title="9. Données personnelles">
        <p>
          Les informations collectées lors de la commande sont traitées conformément à notre
          politique de confidentialité et au Règlement Général sur la Protection des Données (RGPD).
        </p>
      </Section>

      <Section title="10. Litiges">
        <p>
          En cas de litige, une solution amiable sera recherchée prioritairement. À défaut, les
          tribunaux compétents du ressort du siège social de NovaShop seront seuls compétents,
          sous réserve des dispositions légales impératives applicables aux consommateurs.
        </p>
      </Section>

      <p className="mt-10 text-xs text-muted/70">Dernière mise à jour : juin 2026</p>
    </article>
  )
}
