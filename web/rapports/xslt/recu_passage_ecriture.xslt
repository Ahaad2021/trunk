<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format">
<xsl:template match="/">
	<fo:root>
		<xsl:call-template name="page_layout_A4_portrait"></xsl:call-template>
		<xsl:apply-templates select="recu_passage_ecriture"/>
	</fo:root>
</xsl:template>

<xsl:include href="page_layout.xslt"/>
<xsl:include href="header.xslt"/>
<xsl:include href="footer.xslt"/>
<xsl:include href="lib.xslt"/>

<xsl:template match="recu_passage_ecriture">
	<fo:page-sequence master-reference="main" font-size="10pt" font-family="Helvetica">
		<xsl:apply-templates select="header"/>
		<xsl:call-template name="footer"></xsl:call-template>
		<fo:flow flow-name="xsl-region-body">
			<xsl:apply-templates select="header_contextuel"/>
			<xsl:call-template name="titre_niv1"><xsl:with-param name="titre" select="'Détails'"/></xsl:call-template>

<fo:table width="100%" table-layout="fixed">
				<fo:table-column column-width="proportional-column-width(2)"/>
				<fo:table-column column-width="proportional-column-width(2)"/>
				<fo:table-column column-width="proportional-column-width(2)"/>
				<fo:table-column column-width="proportional-column-width(2)"/>
				<fo:table-column column-width="proportional-column-width(2)"/>
				<fo:table-column column-width="proportional-column-width(2)"/>

                <fo:table-header>
					<fo:table-row font-weight="bold">
						<fo:table-cell> 
 	              <fo:block>Numéro compte client</fo:block> 
 		            </fo:table-cell>
						<fo:table-cell>
							<fo:block>Num cpte débit</fo:block>
						</fo:table-cell>
                        <fo:table-cell>
							<fo:block>Nom cpte débit</fo:block>
						</fo:table-cell>
                        <fo:table-cell>
							<fo:block>Num cpte crédit</fo:block>
						</fo:table-cell>
                        <fo:table-cell>
							<fo:block>Nom cpte crédit</fo:block>
						</fo:table-cell>
                        <fo:table-cell>
							<fo:block>Montant</fo:block>
						</fo:table-cell>
                        </fo:table-row>
				</fo:table-header>		
				<xsl:apply-templates select="body"/>	
			</fo:table>
        </fo:flow>
    </fo:page-sequence>
</xsl:template>

<xsl:template match="body">
	<fo:table-body>
		<fo:table-row>
			<fo:table-cell>
				<fo:block><xsl:value-of select="num_client"/></fo:block>
			</fo:table-cell>
			<fo:table-cell>
				<fo:block><xsl:value-of select="num_cpte_deb"/></fo:block>
			</fo:table-cell>
			<fo:table-cell>
				<fo:block><xsl:value-of select="nom_cpte_deb"/></fo:block>
			</fo:table-cell>
			<fo:table-cell>
				<fo:block><xsl:value-of select="num_cpte_cre"/></fo:block>
			</fo:table-cell>
			<fo:table-cell>
				<fo:block><xsl:value-of select="nom_cpte_cre"/></fo:block>
			</fo:table-cell>
			<fo:table-cell>
				<fo:block><xsl:value-of select="montant"/></fo:block>
			</fo:table-cell>
		</fo:table-row>	
	</fo:table-body>
</xsl:template>

<xsl:template match="header_contextuel">
	<xsl:apply-templates select="infos_synthetiques"/>
</xsl:template>

<xsl:template match="infos_synthetiques">
	<xsl:call-template name="titre_niv1"><xsl:with-param name="titre" select="'Informations synthétiques'"/></xsl:call-template>
	<fo:list-block>
		<fo:list-item>
			<fo:list-item-label><fo:block></fo:block></fo:list-item-label>
			<fo:list-item-body><fo:block><xsl:value-of select="$point_liste" disable-output-escaping="yes"/>Opérateur: <xsl:value-of select="login"/></fo:block></fo:list-item-body>
		</fo:list-item>
		<fo:list-item>
			<fo:list-item-label><fo:block></fo:block></fo:list-item-label>
			<fo:list-item-body><fo:block><xsl:value-of select="$point_liste" disable-output-escaping="yes"/>Date: <xsl:value-of select="date"/></fo:block></fo:list-item-body>
		</fo:list-item>
		<fo:list-item>
			<fo:list-item-label><fo:block></fo:block></fo:list-item-label>
			<fo:list-item-body><fo:block><xsl:value-of select="$point_liste" disable-output-escaping="yes"/>Libellé écriture: <xsl:value-of select="libelle"/></fo:block></fo:list-item-body>
		</fo:list-item>
		<fo:list-item> 
 	  <fo:list-item-label><fo:block></fo:block></fo:list-item-label> 
 	     <fo:list-item-body><fo:block><xsl:value-of select="$point_liste" disable-output-escaping="yes"/>Numéro transaction : <xsl:value-of select="num_trans"/></fo:block></fo:list-item-body> 
 	  </fo:list-item>
		<fo:list-item>
			<fo:list-item-label><fo:block></fo:block></fo:list-item-label>
			<fo:list-item-body><fo:block><xsl:value-of select="$point_liste" disable-output-escaping="yes"/>Login initiateur : <xsl:value-of select="login_initiateur"/></fo:block></fo:list-item-body>
		</fo:list-item>


	</fo:list-block>
</xsl:template>

</xsl:stylesheet>
		

		
		
		
		