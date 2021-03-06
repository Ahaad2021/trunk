<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format">

<xsl:template match="/">
	<fo:root>
		<xsl:call-template name="page_layout_A4_paysage"></xsl:call-template>
		<xsl:apply-templates select="bilan"/>
	</fo:root>
</xsl:template>

<xsl:include href="page_layout.xslt"/>
<xsl:include href="header.xslt"/>
<xsl:include href="footer.xslt"/>
<xsl:include href="lib.xslt"/>

<xsl:template match="bilan">
	<fo:page-sequence master-reference="main" font-size="10pt" font-family="Helvetica">
		<xsl:apply-templates select="header"/>
		<xsl:call-template name="footer"></xsl:call-template>
		<fo:flow flow-name="xsl-region-body">
			<fo:table border="none" border-collapse="separate" border-separation.inline-progression-direction="10pt">
				<fo:table-column column-width="2cm"/>
				<fo:table-column column-width="6cm" border-left-width="0.3pt" border-left-style="solid" border-left-color="gray"/>
				<fo:table-column column-width="3cm" border-left-width="0.3pt" border-left-style="solid" border-left-color="gray"/>
				<fo:table-column column-width="3cm" border-left-width="0.3pt" border-left-style="solid" border-left-color="gray"/>
				<fo:table-column column-width="3cm" border-left-width="0.3pt" border-left-style="solid" border-left-color="gray"/>
				<fo:table-column column-width="2cm" border-left-width="0.3pt" border-left-style="solid" border-left-color="gray"/>
				<fo:table-column column-width="6cm" border-left-width="0.3pt" border-left-style="solid" border-left-color="gray"/>
				<fo:table-column column-width="3cm" border-left-width="0.3pt" border-left-style="solid" border-left-color="gray"/>

				<fo:table-header>
					<fo:table-row font-weight="bold">
						<fo:table-cell number-columns-spanned="5">
							<fo:block text-align="center">ACTIF</fo:block>
						</fo:table-cell>
						<fo:table-cell number-columns-spanned="3">
							<fo:block text-align="center">PASSIF</fo:block>
						</fo:table-cell>
					</fo:table-row>
					<fo:table-row font-weight="bold">
						<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
							<fo:block text-align="left">N??</fo:block>
						</fo:table-cell>
						<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
							<fo:block text-align="left">Libell?? compte</fo:block>
						</fo:table-cell>
						<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
							<fo:block text-align="center">Brut</fo:block>
						</fo:table-cell>
						<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
							<fo:block text-align="center">AmtProv</fo:block>
						</fo:table-cell>
						<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
							<fo:block text-align="center">Net</fo:block>
						</fo:table-cell>
						<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
							<fo:block text-align="left">N??</fo:block>
						</fo:table-cell>
						<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
							<fo:block text-align="left">Libell?? compte</fo:block>
						</fo:table-cell>
						<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
							<fo:block text-align="center">Montant</fo:block>
						</fo:table-cell>
					</fo:table-row>
				</fo:table-header>

				<fo:table-body>
					<xsl:apply-templates select="compte"/>					
				</fo:table-body>
			</fo:table>
			<xsl:apply-templates select="agences"/>					
		</fo:flow>
	</fo:page-sequence>
</xsl:template>
<xsl:template match="agences">
<xsl:if test="enreg_agence/is_siege='true'">
<xsl:call-template name="titre_niv1"><xsl:with-param name="titre" select="'Liste des agences consolid??es'"/></xsl:call-template>
	<fo:table border="none" border-collapse="separate" border-separation.inline-progression-direction="10pt">
				<fo:table-column column-width="4cm"/>
				<fo:table-column column-width="15cm" border-left-width="0.3pt" border-left-style="solid" border-left-color="gray"/>
				<fo:table-column column-width="6cm" border-left-width="0.3pt" border-left-style="solid" border-left-color="gray"/>
	<fo:table-header>
		<fo:table-row font-weight="bold">
			<fo:table-cell>
				<fo:block text-align="center">Identifiant agence </fo:block>
			</fo:table-cell>
			<fo:table-cell>
				<fo:block text-align="center"> Libell?? agence  </fo:block>
			</fo:table-cell>
			<fo:table-cell>
				<fo:block text-align="center"> Date dernier mouvement </fo:block>
			</fo:table-cell>
	</fo:table-row>
	</fo:table-header>
	<fo:table-body>
	  <xsl:apply-templates select="enreg_agence"/>
	</fo:table-body>
</fo:table>	
</xsl:if>	
</xsl:template>

<xsl:template match="enreg_agence">
 <fo:table-row>
			<fo:table-cell>
				<fo:block text-align="center"><xsl:value-of select="id_ag"/></fo:block>
			</fo:table-cell>
			<fo:table-cell>
				<fo:block text-align="center"><xsl:value-of select="libel_ag"/></fo:block>
			</fo:table-cell>
			<fo:table-cell>
				<fo:block text-align="center"><xsl:value-of select="date_max"/></fo:block>
			</fo:table-cell>
		</fo:table-row>
</xsl:template>

<xsl:template match="compte">
	<xsl:if test="@total = '1'">
	<fo:table-row font-weight="bold">
		<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
			<fo:block text-align="left"><xsl:value-of select="compte_actif"/></fo:block>
		</fo:table-cell>
		<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
			<fo:block text-align="left"><xsl:value-of select="libel_actif"/></fo:block>
		</fo:table-cell>
		<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
			<fo:block text-align="right"><xsl:value-of select="solde_actif"/></fo:block>
		</fo:table-cell>
		<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
			<fo:block text-align="right"><xsl:value-of select="amort_actif"/></fo:block>
		</fo:table-cell>
		<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
			<fo:block text-align="right"><xsl:value-of select="net_actif"/></fo:block>
		</fo:table-cell>
		<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
			<fo:block text-align="left"><xsl:value-of select="compte_passif"/></fo:block>
		</fo:table-cell>
		<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
			<fo:block text-align="left"><xsl:value-of select="libel_passif"/></fo:block>
		</fo:table-cell>
		<fo:table-cell border-top-width="0.5pt" border-top-style="solid" border-top-color="black" border-bottom-width="0.5pt" border-bottom-style="solid" border-bottom-color="black">
			<fo:block text-align="right"><xsl:value-of select="solde_passif"/></fo:block>
		</fo:table-cell>
	</fo:table-row>
	</xsl:if>
	<xsl:if test="@total = '0'">
		<fo:table-row>
		<xsl:choose>
			<xsl:when test="@nivact = '2'">		
				<fo:table-cell font-weight="bold">
					<fo:block text-align="left" space-before.optimum="0.2cm"><xsl:value-of select="compte_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell font-weight="bold">
					<fo:block text-align="left" space-before.optimum="0.2cm"><xsl:value-of select="libel_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell font-weight="bold">
					<fo:block text-align="right" space-before.optimum="0.2cm"><xsl:value-of select="solde_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell font-weight="bold">
					<fo:block text-align="right" space-before.optimum="0.2cm"><xsl:value-of select="amort_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell font-weight="bold">
					<fo:block text-align="right" space-before.optimum="0.2cm"><xsl:value-of select="net_actif"/></fo:block>
				</fo:table-cell>
			</xsl:when>
			<xsl:when test="@nivact = '3'">
				<fo:table-cell>
					<fo:block text-align="center" space-before.optimum="0.2cm"><xsl:value-of select="compte_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="left" space-before.optimum="0.2cm"><xsl:value-of select="libel_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="right" space-before.optimum="0.2cm"><xsl:value-of select="solde_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="right" space-before.optimum="0.2cm"><xsl:value-of select="amort_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="right" space-before.optimum="0.2cm"><xsl:value-of select="net_actif"/></fo:block>
				</fo:table-cell>
			</xsl:when>
			<xsl:otherwise>
				<fo:table-cell>
					<fo:block text-align="right" space-before.optimum="0.2cm" font-style="italic"><xsl:value-of select="compte_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="left" space-before.optimum="0.2cm" font-style="italic"><xsl:value-of select="libel_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="right" space-before.optimum="0.2cm" font-style="italic"><xsl:value-of select="solde_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="right" space-before.optimum="0.2cm" font-style="italic"><xsl:value-of select="amort_actif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="right" font-style="italic"><xsl:value-of select="net_actif"/></fo:block>
				</fo:table-cell>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:choose>
			<xsl:when test="@nivpass = '2'">
				<fo:table-cell font-weight="bold">
					<fo:block text-align="left" space-before.optimum="0.2cm"><xsl:value-of select="compte_passif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell font-weight="bold">
					<fo:block text-align="left" space-before.optimum="0.2cm"><xsl:value-of select="libel_passif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell font-weight="bold">
					<fo:block text-align="right" space-before.optimum="0.2cm"><xsl:value-of select="solde_passif"/></fo:block>
				</fo:table-cell>
			</xsl:when>
			<xsl:when test="@nivpass = '3'">
				<fo:table-cell>
					<fo:block text-align="center" space-before.optimum="0.2cm"><xsl:value-of select="compte_passif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="left" space-before.optimum="0.2cm"><xsl:value-of select="libel_passif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="right" space-before.optimum="0.2cm"><xsl:value-of select="solde_passif"/></fo:block>
				</fo:table-cell>
			</xsl:when>
			<xsl:otherwise>
				<fo:table-cell>
					<fo:block text-align="right" space-before.optimum="0.2cm" font-style="italic"><xsl:value-of select="compte_passif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="left" space-before.optimum="0.2cm" font-style="italic"><xsl:value-of select="libel_passif"/></fo:block>
				</fo:table-cell>
				<fo:table-cell>
					<fo:block text-align="right" space-before.optimum="0.2cm" font-style="italic"><xsl:value-of select="solde_passif"/></fo:block>
				</fo:table-cell>			 
			</xsl:otherwise>
		</xsl:choose>
		</fo:table-row>
	</xsl:if>
</xsl:template>

</xsl:stylesheet>
