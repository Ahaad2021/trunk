<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format" version="1.0">
  <xsl:template match="/">
    <fo:root>
      <xsl:call-template name="page_layout_A4_paysage"/>
      <xsl:apply-templates select="situation_analytique_client"/>
    </fo:root>
  </xsl:template>
  <xsl:include href="page_layout.xslt"/>
  <xsl:include href="header.xslt"/>
  <xsl:include href="footer.xslt"/>
  <xsl:include href="lib.xslt"/>
  <xsl:template match="situation_analytique_client">
    <fo:page-sequence master-reference="main" font-size="10pt" font-family="Helvetica">
      <xsl:apply-templates select="header"/>
      <xsl:call-template name="footer"/>
      <fo:flow flow-name="xsl-region-body">
        <xsl:apply-templates select="header_contextuel"/>
        <xsl:apply-templates select="credits"/>
        <xsl:apply-templates select="epargne"/>
        <xsl:apply-templates select="ord_permanent"/>
      </fo:flow>
    </fo:page-sequence>
  </xsl:template>
  <xsl:template match="header_contextuel">
    <xsl:call-template name="titre_niv1">
      <xsl:with-param name="titre" select="'Détails du client'"/>
    </xsl:call-template>
    <fo:list-item>
      <fo:list-item-label>
        <fo:block/>
      </fo:list-item-label>
      <fo:list-item-body>
        <fo:block><xsl:value-of select="$point_liste" disable-output-escaping="yes"/>Numéro : <xsl:value-of select="num_client"/></fo:block>
      </fo:list-item-body>
    </fo:list-item>
    <fo:list-item>
      <fo:list-item-label>
        <fo:block/>
      </fo:list-item-label>
      <fo:list-item-body>
        <fo:block><xsl:value-of select="$point_liste" disable-output-escaping="yes"/>Nom : <xsl:value-of select="nom_client"/></fo:block>
      </fo:list-item-body>
    </fo:list-item>
  </xsl:template>
  <xsl:template match="epargne">
    <xsl:call-template name="titre_niv1">
      <xsl:with-param name="titre" select="'Situation Epargne'"/>
    </xsl:call-template>
    <fo:table border-collapse="collapse" border-separation.inline-progression-direction="10pt" width="100%" table-layout="fixed">
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-header>
        <fo:table-row font-weight="bold">
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">N° Compte</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Produit Epargne</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Date ouverture</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Etat compte</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Solde</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Devise</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Montant bloque</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Montant disponible</fo:block>
          </fo:table-cell>
        </fo:table-row>
      </fo:table-header>
      <fo:table-body>
        <xsl:apply-templates select="compte"/>
      </fo:table-body>
    </fo:table>
  </xsl:template>
  <xsl:template match="compte">
    <fo:table-row>
      <fo:table-cell display-align="center" border="0.1pt solid gray" padding-right="3pt">
        <fo:block text-align="right">
          <xsl:value-of select="num_cpte"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="prod_ep"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="date_ouvert"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="etat_cpte"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="solde"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="devise"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="mnt_bloq"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="mnt_disponible"/>
        </fo:block>
      </fo:table-cell>
    </fo:table-row>
  </xsl:template>
  <xsl:template match="ord_permanent">
    <xsl:call-template name="titre_niv1">
      <xsl:with-param name="titre" select="'Situation Ordre Permanent'"/>
    </xsl:call-template>
    <fo:table border-collapse="collapse" border-separation.inline-progression-direction="10pt" width="100%" table-layout="fixed">
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-header>
        <fo:table-row font-weight="bold">
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">N° Compte Destinataire</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Produit Epargne</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Date ouverture</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Cotisation/Mise</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Périodicité</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Date Fin</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Solde Actuel</fo:block>
          </fo:table-cell>
        </fo:table-row>
      </fo:table-header>
      <fo:table-body>
        <xsl:apply-templates select="compte_ord"/>
      </fo:table-body>
    </fo:table>
  </xsl:template>
  <xsl:template match="compte_ord">
    <fo:table-row>
      <fo:table-cell display-align="center" border="0.1pt solid gray" padding-right="3pt">
        <fo:block text-align="right">
          <xsl:value-of select="num_cpte_ord"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="prod"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="date_ouverture"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="montant"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="periodicite"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="date_fin"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="mnt_solde"/>
        </fo:block>
      </fo:table-cell>
    </fo:table-row>
  </xsl:template>
  <xsl:template match="credits">
    <xsl:call-template name="titre_niv1">
      <xsl:with-param name="titre" select="'Situation crédits'"/>
    </xsl:call-template>
    <fo:table border-collapse="collapse" border-separation.inline-progression-direction="10pt" width="100%" table-layout="fixed">
      <fo:table-column column-width="proportional-column-width(2)"/>
      <fo:table-column column-width="proportional-column-width(2)"/>
      <fo:table-column column-width="proportional-column-width(2)"/>
      <fo:table-column column-width="proportional-column-width(2)"/>
      <fo:table-column column-width="proportional-column-width(2)"/>
      <fo:table-column column-width="proportional-column-width(2)"/>
      <fo:table-column column-width="proportional-column-width(2)"/>
      <fo:table-column column-width="proportional-column-width(2)"/>
      <fo:table-column column-width="proportional-column-width(2)"/>
      <fo:table-header>
        <fo:table-row font-weight="bold">
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">N° dossier</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Capital du</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Interets dus</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Garanties dues</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Capital restant</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Interet restant</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Garantie restante</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center">Etat credit</fo:block>
          </fo:table-cell>
        </fo:table-row>
      </fo:table-header>
      <fo:table-body>
        <xsl:apply-templates select="dossier"/>
      </fo:table-body>
    </fo:table>
  </xsl:template>
  <xsl:template match="dossier">
    <fo:table-row>
      <fo:table-cell display-align="center" border="0.1pt solid gray" padding-right="3pt">
        <fo:block text-align="right">
          <xsl:value-of select="id_doss"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="cap_du"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="int_du"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="gar_du"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="cap_rest"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="int_rest"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="gar_rest"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid gray">
        <fo:block text-align="center">
          <xsl:value-of select="cre_retard_etat_max"/>
        </fo:block>
      </fo:table-cell>
    </fo:table-row>
  </xsl:template>
</xsl:stylesheet>
