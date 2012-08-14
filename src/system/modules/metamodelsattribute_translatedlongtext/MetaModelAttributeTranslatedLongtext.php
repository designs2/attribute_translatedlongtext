<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package     MetaModels
 * @subpackage  AttributeText
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright   CyberSpectrum
 * @license     private
 * @filesource
 */

/**
 * This is the MetaModelAttribute class for handling text fields.
 *
 * @package     MetaModels
 * @subpackage  AttributeText
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class MetaModelAttributeTranslatedLongtext
extends MetaModelAttributeComplex
implements IMetaModelAttributeTranslated
{

	public function getAttributeSettingNames()
	{
		return array_merge(parent::getAttributeSettingNames(), array(
			'parentCheckbox',
			'titleField',
			'width50',
			'insertBreak',
			'sortingField',
			'filteredField',
			'searchableField',
			'mandatory',
			'defValue',
			'uniqueItem',
			'formatPrePost',
			'format',
			'editGroups'
		));
	}

	public function getFieldDefinition()
	{
		$arrLanguages = array();
		$arrFieldDef['inputType'] = 'longtext';
		/*
			  foreach((array)$this->getMetaModel()->getAvailableLanguages() as $strLangCode)
			  {
			  $arrLanguages[$strLangCode] = $GLOBALS['TL_LANG']['LNG'][$strLangCode];
			  }
			  $arrFieldDef=parent::getFieldDefinition();
			  $arrFieldDef['inputType'] = 'multiColumnWizard';
			  $arrFieldDef['eval']['minCount'] = count($arrLanguages);
			  $arrFieldDef['eval']['maxCount'] = count($arrLanguages);
			  $arrFieldDef['eval']['columnFields'] = array
			  (
			  'langcode' => array
			  (
			  'label'                 => &$GLOBALS['TL_LANG']['tl_metamodel_translatedtext']['langcode'],
			  'exclude'               => true,
			  'inputType'             => 'select',
			  'options'               => $arrLanguages,
			  'eval' 			=> array(
			  'valign' => 'top',
			  'style' => 'width:250px',
			  'includeBlankOption'=>true,
			  'chosen'=>true
			  )
			  ),
			  'value' => array
			  (
			  'label'                 => &$GLOBALS['TL_LANG']['tl_metamodel_translatedtext']['value'],
			  'exclude'               => true,
			  'inputType'             => 'text',
			  'eval' 			=> array('style' => 'width:250px;height:60px;')
			  ),
			  );
		 */
		return $arrFieldDef;
	}

	public function parseValue($arrRowData, $strOutputFormat = 'text', $objSettings = null)
	{
		$arrResult = parent::parseValue($arrRowData, $strOutputFormat);
		$arrResult['text'] = $arrRowData[$this->getColName()]['value'];
		return $arrResult;
	}

	public function getDataFor($arrIds)
	{
		$strActiveLanguage = $this->getMetaModel()->getActiveLanguage();
		$strFallbackLanguage = $this->getMetaModel()->getFallbackLanguage();

		$arrReturn = $this->getTranslatedDataFor($arrIds, $strActiveLanguage);

		// second round, fetch fallback languages if not all items could be resolved.
		if ((count($arrReturn) < count($arrIds)) && ($strActiveLanguage != $strFallbackLanguage))
		{
			$arrFallbackIds = array();
			foreach ($arrIds as $intId)
			{
				if (empty($arrReturn[$intId]))
				{
					$arrFallbackIds[] = $intId;
				}
			}

			if ($arrFallbackIds)
			{
				$arrFallbackData = $this->getTranslatedDataFor($arrFallbackIds, $strFallbackLanguage);
				// cannot use array_merge here as it would renumber the keys.
				foreach ($arrFallbackData as $intId => $arrValue)
				{
					$arrReturn[$intId] = $arrValue;
				}
			}
		}
		return $arrReturn;
	}

	public function setDataFor($arrValues)
	{
		foreach ($this->getMetaModel()->getAvailableLanguages() as $strLangCode)
		{
			$this->setTranslatedDataFor($arrValues, $strLangCode);
		}
	}

	public function unsetDataFor($arrIds)
	{
		foreach ($this->getMetaModel()->getAvailableLanguages() as $strLangCode)
		{
			$this->unsetValueFor($arrIds, $strLangCode);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * Fetch filter options from foreign table.
	 *
	 */
	public function getFilterOptions($arrIds = array())
	{
		$objDB = Database::getInstance();

		if ($arrIds)
		{
			$strWhereIds = ' AND item_id IN (' . implode(',', $arrIds) . ')';
		}

		$objValue = $objDB->prepare('SELECT * FROM tl_metamodel_translatedlongtext WHERE att_id=? AND langcode=? ' . $strWhereIds)
				->execute($this->get('id'), $this->getMetaModel()->getActiveLanguage());

		$arrReturn = array();
		while ($objValue->next())
		{
			$arrReturn[$objValue->value] = $objValue->value;
		}
		return $arrReturn;
	}

	/////////////////////////////////////////////////////////////////
	// interface IMetaModelAttributeTranslated
	/////////////////////////////////////////////////////////////////

	public function setTranslatedDataFor($arrValues, $strLangCode)
	{
		$strMetaModelTableName = $this->getMetaModel()->getTableName();
		$arrReturn = array();

		if ($strTableName && $strColNameId)
		{
			$strColNameValue = $this->get('select_column');
			$objDB = Database::getInstance();
			$strQuery = sprintf('UPDATE %1$s SET %2$s=? WHERE %1$s.id=?', $strMetaModelTableName, $this->getColName());
			foreach ($arrValues as $intItemId => $arrValue)
			{
				$objQuery = $objDB->prepare($strQuery)->execute($arrValue[$strColNameId], $intItemId);
			}
		}
	}

	/**
	 * Get values for the given items in a certain language.
	 */
	public function getTranslatedDataFor($arrIds, $strLangCode)
	{
		$objDB = Database::getInstance();
		$objValue = $objDB->prepare('SELECT * FROM tl_metamodel_translatedlongtext WHERE att_id=? AND langcode=? AND item_id IN (' . implode(',', $arrIds) . ')')
				->execute($this->get('id'), $strLangCode);
		$arrReturn = array();
		while ($objValue->next())
		{
			$arrReturn[$objValue->item_id] = $objValue->row();
		}
		return $arrReturn;
	}

	/**
	 * Remove values for items in a certain lanugage.
	 */
	public function unsetValueFor($arrIds, $strLangCode)
	{
		$objValue = $objDB->prepare('DELETE FROM tl_metamodel_translatedlongtext WHERE att_id=? AND langcode=? AND item_id IN (' . implode(',', $arrIds) . ')')
				->execute($this->get('id'), $strLangCode);
	}
}

?>