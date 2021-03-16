<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format" version="1.0">
  <xsl:template match="/">
    <fo:root>
      <xsl:call-template name="page_layout_A4_paysage"/>
      <xsl:apply-templates select="appel_fonds"/>
    </fo:root>
  </xsl:template>
  <xsl:include href="page_layout.xslt"/>
  <xsl:include href="header.xslt"/>
  <xsl:include href="criteres_recherche.xslt"/>
  <xsl:include href="footer.xslt"/>
  <xsl:include href="lib.xslt"/>
  <xsl:template match="appel_fonds">
    <fo:page-sequence master-reference="main" font-size="10pt" font-family="Helvetica">
      <xsl:apply-templates select="header"/>
      <xsl:call-template name="footer"/>
      <fo:flow flow-name="xsl-region-body">
        <xsl:apply-templates select="header_contextuel"/>
        <xsl:call-template name="titre_niv1">
          <xsl:with-param name="titre" select="'Détails'"/>
        </xsl:call-template>
        <xsl:apply-templates select="gestionnaire"/>
        <xsl:apply-templates select="recapitulatif"/>
      </fo:flow>
    </fo:page-sequence>
  </xsl:template>
  <xsl:template match="gestionnaire">
    <xsl:call-template name="titre_niv1">
      <xsl:with-param name="titre">
        <xsl:value-of select="agent_gest"/>
      </xsl:with-param>
    </xsl:call-template>
    <fo:table border-collapse="collapse" border-separation.inline-progression-direction="10pt" width="100%" table-layout="fixed">
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(3)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(2)"/>
      <fo:table-column column-width="proportional-column-width(3)"/>
      <fo:table-column column-width="proportional-column-width(3)"/>
      <fo:table-column column-width="proportional-column-width(3)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(2)"/>
      <fo:table-header>
        <fo:table-row font-weight="bold">
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="1px" border-top-width="1px" border-right-width="1px" border-bottom-width="1px" border-style="solid">
            <fo:block text-align="center">Client</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="1px" border-top-width="1px" border-right-width="1px" border-bottom-width="1px" border-style="solid">
            <fo:block text-align="center">Name</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="1px" border-top-width="1px" border-right-width="1px" border-bottom-width="1px" border-style="solid">
            <fo:block text-align="center">File</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="1px" border-top-width="1px" border-right-width="1px" border-bottom-width="1px" border-style="solid">
            <fo:block text-align="center">Product</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="1px" border-top-width="1px" border-right-width="1px" border-bottom-width="1px" border-style="solid">
            <fo:block text-align="center">Request date</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="1px" border-top-width="1px" border-right-width="1px" border-bottom-width="1px" border-style="solid">
            <fo:block text-align="center">Amount requested</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="1px" border-top-width="1px" border-right-width="1px" border-bottom-width="1px" border-style="solid">
            <fo:block text-align="center">Object</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="1px" border-top-width="1px" border-right-width="1px" border-bottom-width="1px" border-style="solid">
            <fo:block text-align="center">Detail</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="1px" border-top-width="1px" border-right-width="1px" border-bottom-width="1px" border-style="solid">
            <fo:block text-align="center">Duration (months)</fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="1px" border-top-width="1px" border-right-width="1px" border-bottom-width="1px" border-style="solid">
            <fo:block text-align="center">File status</fo:block>
          </fo:table-cell>
        </fo:table-row>
      </fo:table-header>
      <fo:table-body>
        <xsl:apply-templates select="ligneCredit"/>
      </fo:table-body>
    </fo:table>
  </xsl:template>
  <xsl:template match="header_contextuel">
    <xsl:apply-templates select="criteres_recherche"/>
  </xsl:template>
  <xsl:template match="ligneCredit">
    <xsl:apply-templates select="infosCreditSolidiaire"/>
    <xsl:apply-templates select="detailCredit"/>
  </xsl:template>
  <xsl:template match="infosCreditSolidiaire">
    <fo:table-row>
      <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-top-width="0.5px" border-style="solid">
        <fo:block text-align="center" font-weight="bold">
          <xsl:value-of select="num_client"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid black" border-top-width="0.5px" border-style="solid">
        <fo:block text-align="left" font-weight="bold">
          <xsl:value-of select="nom_client"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid black" border-top-width="0.5px" border-style="solid">
        <fo:block text-align="center" font-weight="bold">
          <xsl:value-of select="no_dossier"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid black" border-top-width="0.5px" border-style="solid">
        <fo:block text-align="center" font-weight="bold">
          <xsl:value-of select="prd_credit"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid black" border-top-width="0.5px" border-style="solid">
        <fo:block text-align="center" font-weight="bold">
          <xsl:value-of select="date_dde"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid black" border-top-width="0.5px" border-style="solid">
        <fo:block text-align="right" font-weight="bold">
          <xsl:value-of select="montant_dde"/>
          <xsl:value-of select="devise"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid black" border-top-width="0.5px" border-style="solid">
        <fo:block text-align="left" font-weight="bold">
          <xsl:value-of select="obj_dde"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid black" border-top-width="0.5px" border-style="solid">
        <fo:block text-align="left" wrap-option="no-wrap" font-weight="bold">
          <xsl:value-of select="detail_obj_dde"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid black" border-top-width="0.5px" border-style="solid">
        <fo:block text-align="center" font-weight="bold">
          <xsl:value-of select="duree"/>
        </fo:block>
      </fo:table-cell>
      <fo:table-cell display-align="center" border="0.1pt solid black" border-top-width="0.5px" border-right-width="0.5px" border-style="solid">
        <fo:block text-align="center" wrap-option="no-wrap" font-weight="bold">
          <xsl:value-of select="etat"/>
        </fo:block>
      </fo:table-cell>
    </fo:table-row>
  </xsl:template>
  <xsl:template match="detailCredit">
    <xsl:choose>
      <xsl:when test="membre_gs=&quot;OUI&quot;">
        <fo:table-row>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-style="solid">
            <fo:block text-align="center" font-style="italic">
              <xsl:value-of select="num_client"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="left" font-style="italic">
              <xsl:value-of select="nom_client"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center" font-style="italic">
              <xsl:value-of select="no_dossier"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center" font-style="italic">
              <xsl:value-of select="prd_credit"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center" font-style="italic">
              <xsl:value-of select="date_dde"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="right" font-style="italic">
              <xsl:value-of select="montant_dde"/>
              <xsl:value-of select="devise"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="left" font-style="italic">
              <xsl:value-of select="obj_dde"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="left" wrap-option="no-wrap" font-style="italic">
              <xsl:value-of select="detail_obj_dde"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid gray">
            <fo:block text-align="center" font-style="italic">
              <xsl:value-of select="duree"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-right-width="0.5px" border-style="solid">
            <fo:block text-align="center" wrap-option="no-wrap" font-style="italic">
              <xsl:value-of select="etat"/>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
      </xsl:when>
      <xsl:otherwise>
        <fo:table-row>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-top-width="0.5px" border-right-width="0.5px" border-bottom-width="0.5px" border-style="solid">
            <fo:block text-align="center">
              <xsl:value-of select="num_client"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-top-width="0.5px" border-right-width="0.5px" border-bottom-width="0.5px" border-style="solid">
            <fo:block>
              <xsl:value-of select="nom_client"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-top-width="0.5px" border-right-width="0.5px" border-bottom-width="0.5px" border-style="solid">
            <fo:block text-align="center">
              <xsl:value-of select="no_dossier"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-top-width="0.5px" border-right-width="0.5px" border-bottom-width="0.5px" border-style="solid">
            <fo:block text-align="center">
              <xsl:value-of select="prd_credit"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-top-width="0.5px" border-right-width="0.5px" border-bottom-width="0.5px" border-style="solid">
            <fo:block text-align="center">
              <xsl:value-of select="date_dde"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-top-width="0.5px" border-right-width="0.5px" border-bottom-width="0.5px" border-style="solid">
            <fo:block text-align="right">
              <xsl:value-of select="montant_dde"/>
              <xsl:value-of select="devise"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-top-width="0.5px" border-right-width="0.5px" border-bottom-width="0.5px" border-style="solid">
            <fo:block text-align="left">
              <xsl:value-of select="obj_dde"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-top-width="0.5px" border-right-width="0.5px" border-bottom-width="0.5px" border-style="solid">
            <fo:block text-align="left" wrap-option="no-wrap">
              <xsl:value-of select="detail_obj_dde"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-top-width="0.5px" border-right-width="0.5px" border-bottom-width="0.5px" border-style="solid">
            <fo:block text-align="center">
              <xsl:value-of select="duree"/>
            </fo:block>
          </fo:table-cell>
          <fo:table-cell display-align="center" border="0.1pt solid black" border-left-width="0.5px" border-top-width="0.5px" border-right-width="0.5px" border-bottom-width="0.5px" border-style="solid">
            <fo:block text-align="center" wrap-option="no-wrap">
              <xsl:value-of select="etat"/>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="recapitulatif">
    <xsl:call-template name="titre_niv1">
      <xsl:with-param name="titre" select="'Total'"/>
    </xsl:call-template>
    <fo:table border-collapse="separate" border-separation.inline-progression-direction="10pt" width="40%" table-layout="fixed">
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-column column-width="proportional-column-width(1)"/>
      <fo:table-body>
        <fo:table-row>
          <fo:table-cell>
            <fo:block text-align="left" font-weight="bold">Total number of loans</fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="left">
              <xsl:value-of select="nb_total_credit"/>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
        <fo:table-row>
          <fo:table-cell>
            <fo:block text-align="left" font-style="italic">Number of ordinary loans</fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="left" font-style="italic">
              <xsl:value-of select="nb_credit_ordinaire"/>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
        <fo:table-row>
          <fo:table-cell>
            <fo:block text-align="left" font-style="italic">Number of joint loans</fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="left" font-style="italic">
              <xsl:value-of select="nb_credit_solidaire"/>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
        <fo:table-row>
          <fo:table-cell>
            <fo:block text-align="left" font-weight="bold">Total amount of loans</fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="left">
              <xsl:value-of select="mnt_total_credit"/>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
        <fo:table-row>
          <fo:table-cell>
            <fo:block text-align="left" font-style="italic">Amount of ordinary loans</fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="left" font-style="italic">
              <xsl:value-of select="mnt_credit_ordinaire"/>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
        <fo:table-row>
          <fo:table-cell>
            <fo:block text-align="left" font-style="italic">Amount of joint loans</fo:block>
          </fo:table-cell>
          <fo:table-cell>
            <fo:block text-align="left" font-style="italic">
              <xsl:value-of select="mnt_credit_solidaire"/>
            </fo:block>
          </fo:table-cell>
        </fo:table-row>
      </fo:table-body>
    </fo:table>
  </xsl:template>
</xsl:stylesheet>
