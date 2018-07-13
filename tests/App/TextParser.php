<?php
/**
 * TextParser test class.
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Sławomir Kłos <s.klos@yetiforce.com>
 */

namespace Tests\App;

class TextParser extends \Tests\Base
{
	/**
	 * Test record instance.
	 *
	 * @var \App\TextParser
	 */
	private static $testInstanceRecord;
	/**
	 * Test clean instance.
	 *
	 * @var \App\TextParser
	 */
	private static $testInstanceClean;
	/**
	 * Test clean instance with module.
	 *
	 * @var \App\TextParser
	 */
	private static $testInstanceCleanModule;

	/**
	 * Testing instances creation.
	 */
	public function testInstancesCreation()
	{
		static::$testInstanceClean = \App\TextParser::getInstance();
		$this->assertInstanceOf('\App\TextParser', static::$testInstanceClean, 'Expected clean instance without module of \App\TextParser');

		static::$testInstanceCleanModule = \App\TextParser::getInstance('Leads');
		$this->assertInstanceOf('\App\TextParser', static::$testInstanceCleanModule, 'Expected clean instance with module Leads of \App\TextParser');

		$this->assertInstanceOf('\App\TextParser', \App\TextParser::getInstanceById(\Tests\Entity\C_RecordActions::createLeadRecord()->getId(), 'Leads'), 'Expected instance from lead id and module string of \App\TextParser');

		static::$testInstanceRecord = \App\TextParser::getInstanceByModel(\Tests\Entity\C_RecordActions::createLeadRecord());
		$this->assertInstanceOf('\App\TextParser', static::$testInstanceRecord, 'Expected instance from record model of \App\TextParser');
	}

	/**
	 * Tests empty content condition.
	 */
	public function testEmptyContent()
	{
		$this->assertSame('', static::$testInstanceClean
			->setContent('')
			->parse()
			->getContent(), 'Clean instance: empty content should return empty result');
	}

	/**
	 * Tests base variables list.
	 */
	public function testGetBaseListVariable()
	{
		$arr = static::$testInstanceClean->getBaseListVariable();
		$this->assertInternalType('array', $arr, 'Expected array type');
		$this->assertNotEmpty($arr, 'Expected any related list data');
		foreach ($arr as $option) {
			$this->assertSame(1, \App\TextParser::isVaribleToParse($option['key']), 'Option: ' . $option['label'] . ', value: ' . $option['key'] . ' should be parseable');
		}
	}

	/**
	 * Tests related module variables list.
	 */
	public function testGetRelatedListVariable()
	{
		$arr = static::$testInstanceCleanModule->getRelatedListVariable();
		$this->assertInternalType('array', $arr, 'Expected array type');
		$this->assertNotEmpty($arr, 'Expected any related list data');
		foreach ($arr as $option) {
			$this->assertSame(1, \App\TextParser::isVaribleToParse($option['key']), 'Option: ' . $option['label'] . ', value: ' . $option['key'] . ' should be parseable');
		}
	}

	/**
	 * Tests static methods.
	 */
	public function testStaticMethods()
	{
		$this->assertSame(1, \App\TextParser::isVaribleToParse('$(TestGroup : TestVar)$'), 'Clean instance: string should be parseable');
		$this->assertSame(0, \App\TextParser::isVaribleToParse('$X(TestGroup : TestVar)$'), 'Clean instance: string should be not parseable');
		$this->assertSame((\AppConfig::main('listview_max_textlength') + 3), strlen(\App\TextParser::textTruncate(\Tests\Entity\C_RecordActions::createLoremIpsumText(), false, true)), 'Clean instance: string should be truncated in expexted format (default length)');
		$this->assertSame(13, strlen(\App\TextParser::textTruncate(\Tests\Entity\C_RecordActions::createLoremIpsumText(), 10, true)), 'Clean instance: string should be truncated in expexted format (text length: 10)');

		$this->assertSame((\AppConfig::main('listview_max_textlength') + 993), strlen(\App\TextParser::htmlTruncate(\Tests\Entity\C_RecordActions::createLoremIpsumHtml(), false, true)), 'Clean instance: html should be truncated in expected format (default length)');

		$this->assertSame(1008, strlen(\App\TextParser::htmlTruncate(\Tests\Entity\C_RecordActions::createLoremIpsumHtml(), 10, true)), 'Clean instance: html should be truncated in expected format (text length: 10)');
	}

	/**
	 * Tests empty content condition.
	 */
	public function testUnregisteredPlaceholderFunction()
	{
		$this->assertSame('+  +', static::$testInstanceClean
			->setContent('+ $(notExist : CurrentTime)$ +')
			->parse()
			->getContent(), 'Clean instance: unregistered function placeholder should return empty string');
	}

	/**
	 * Tests general placeholders replacement.
	 */
	public function testGeneralPlaceholders()
	{
		$this->assertSame('+ ' . (new \DateTimeField(null))->getDisplayDate() . ' +', static::$testInstanceClean
			->setContent('+ $(general : CurrentDate)$ +')
			->parse()
			->getContent(), 'Clean instance: $(general : CurrentDate)$ should return current date');
		$this->assertSame('+ ' . \Vtiger_Util_Helper::convertTimeIntoUsersDisplayFormat(date('h:i:s')) . ' +', static::$testInstanceClean
			->setContent('+ $(general : CurrentTime)$ +')
			->parse()
			->getContent(), 'Clean instance: $(general : CurrentTime)$ should return current time');
		$this->assertSame('+ ' . \AppConfig::main('default_timezone') . ' +', static::$testInstanceClean
			->setContent('+ $(general : BaseTimeZone)$ +')
			->parse()
			->getContent(), 'Clean instance: $(general : BaseTimeZone)$ should return system timezone');
		$user = \App\User::getCurrentUserModel();
		$this->assertSame('+ ' . ($user->getDetail('time_zone') ? $user->getDetail('time_zone') : \AppConfig::main('default_timezone')) . ' +', static::$testInstanceClean
			->setContent('+ $(general : UserTimeZone)$ +')
			->parse()
			->getContent(), 'Clean instance: $(general : UserTimeZone)$ should return user timezone');
		$currUser = \App\User::getCurrentUserId();
		\App\User::setCurrentUserId(0);
		$this->assertSame('+ ' . \AppConfig::main('default_timezone') . ' +', static::$testInstanceClean
			->setContent('+ $(general : UserTimeZone)$ +')
			->parse()
			->getContent(), 'Clean instance: $(general : UserTimeZone)$ when current user not set/exist should return default timezone');
		\App\User::setCurrentUserId($currUser);

		$this->assertSame('+ ' . \AppConfig::main('site_URL') . ' +', static::$testInstanceClean
			->setContent('+ $(general : SiteUrl)$ +')
			->parse()
			->getContent(), 'Clean instance: $(general : SiteUrl)$ should return site url');

		$this->assertSame('+ ' . \AppConfig::main('PORTAL_URL') . ' +', static::$testInstanceClean
			->setContent('+ $(general : PortalUrl)$ +')
			->parse()
			->getContent(), 'Clean instance: $(general : PortalUrl)$ should return portal url');

		$this->assertSame('+ PlaceholderNotExist +', static::$testInstanceClean
			->setContent('+ $(general : PlaceholderNotExist)$ +')
			->parse()
			->getContent(), 'Clean instance: $(general : PlaceholderNotExist)$ should return placeholder var name');
	}

	/**
	 * Tests date placeholders replacement.
	 */
	public function testDatePlaceholders()
	{
		$this->assertSame('+ ' . \date('Y-m-d') . ' +', static::$testInstanceClean
			->setContent('+ $(date : now)$ +')
			->parse()
			->getContent(), 'Clean instance: $(date : now)$ should return current date');

		$this->assertSame('+ ' . \date('Y-m-d', \strtotime('+1 day')) . ' +', static::$testInstanceClean
			->setContent('+ $(date : tomorrow)$ +')
			->parse()
			->getContent(), 'Clean instance: $(date : tomorrow)$ should return tommorow date');

		$this->assertSame('+ ' . \date('Y-m-d', \strtotime('-1 day')) . ' +', static::$testInstanceClean
			->setContent('+ $(date : yesterday)$ +')
			->parse()
			->getContent(), 'Clean instance: $(date : yesterday)$ should return yesterday date');

		$this->assertSame('+ ' . \date('Y-m-d', \strtotime('monday this week')) . ' +', static::$testInstanceClean
			->setContent('+ $(date : monday this week)$ +')
			->parse()
			->getContent(), 'Clean instance: $(date : monday this week)$ should return this week monday date');

		$this->assertSame('+ ' . \date('Y-m-d', \strtotime('monday next week')) . ' +', static::$testInstanceClean
			->setContent('+ $(date : monday next week)$ +')
			->parse()
			->getContent(), 'Clean instance: $(date : monday next week)$ should return next week monday date');

		$this->assertSame('+ ' . \date('Y-m-d', \strtotime('first day of this month')) . ' +', static::$testInstanceClean
			->setContent('+ $(date : first day of this month)$ +')
			->parse()
			->getContent(), 'Clean instance: $(date : first day of this month)$ should return this month first day date');

		$this->assertSame('+ ' . \date('Y-m-d', \strtotime('last day of this month')) . ' +', static::$testInstanceClean
			->setContent('+ $(date : last day of this month)$ +')
			->parse()
			->getContent(), 'Clean instance: $(date : last day of this month)$ should return this month last day date');

		$this->assertSame('+ ' . \date('Y-m-d', \strtotime('first day of next month')) . ' +', static::$testInstanceClean
			->setContent('+ $(date : first day of next month)$ +')
			->parse()
			->getContent(), 'Clean instance: $(date : first day of next month)$ should return next month first day date');
	}

	/**
	 * Testing basic field placeholder replacement.
	 */
	public function testBasicFieldPlaceholderReplacement()
	{
		if ((new \App\Db\Query())->select(['crmid'])->from('vtiger_crmentity')->where(['deleted' => 0, 'setype' => 'OSSEmployees', 'smownerid' => \App\User::getCurrentUserId()])
			->limit(1)->exists()) {
			$tmpUser = \App\User::getCurrentUserId();
			\App\User::setCurrentUserId((new \App\Db\Query())->select(['id'])->from('vtiger_users')->where(['status' => 'Active'])->andWhere(['not in', 'id', (new \App\Db\Query())->select(['smownerid'])->from('vtiger_crmentity')->where(['deleted' => 0, 'setype' => 'OSSEmployees'])
				->column()])
				->limit(1)->scalar());
		}
		$text = '+ $(employee : last_name)$ +';
		$this->assertSame('+  +', static::$testInstanceClean
			->setContent($text)
			->parse()
			->getContent(), 'Clean instance: By default employee last name should be empty');
		$this->assertSame('+  +', static::$testInstanceRecord
			->setContent($text)
			->parse()
			->getContent(), 'Record instance: By default employee last name should be empty');
		if (isset($tmpUser)) {
			\App\User::setCurrentUserId($tmpUser);
		}
	}

	/**
	 * Testing basic translate function.
	 */
	public function testTranslate()
	{
		$this->assertSame(
			'+' . \App\Language::translate('LBL_SECONDS') . '==' . \App\Language::translate('LBL_COPY_BILLING_ADDRESS', 'Accounts') . '+',
			static::$testInstanceClean->setContent('+$(translate : LBL_SECONDS)$==$(translate : Accounts|LBL_COPY_BILLING_ADDRESS)$+')->parse()->getContent(),
			'Clean instance: Translations should be equal');
		static::$testInstanceClean->withoutTranslations(true);

		$this->assertSame(
			'+$(translate : LBL_SECONDS)$==$(translate : Accounts|LBL_COPY_BILLING_ADDRESS)$+',
			static::$testInstanceClean->setContent('+$(translate : LBL_SECONDS)$==$(translate : Accounts|LBL_COPY_BILLING_ADDRESS)$+')->parse()->getContent(),
			'Clean instance: Translations should be equal');
		static::$testInstanceClean->withoutTranslations(false);

		$this->assertSame(
			'+' . \App\Language::translate('LBL_SECONDS') . '==' . \App\Language::translate('LBL_COPY_BILLING_ADDRESS', 'Accounts') . '+',
			static::$testInstanceRecord->setContent('+$(translate : LBL_SECONDS)$==$(translate : Accounts|LBL_COPY_BILLING_ADDRESS)$+')->parse()->getContent(),
			'Record instance: Translations should be equal');
		static::$testInstanceRecord->withoutTranslations(true);

		$this->assertSame(
			'+$(translate : LBL_SECONDS)$==$(translate : Accounts|LBL_COPY_BILLING_ADDRESS)$+',
			static::$testInstanceRecord->setContent('+$(translate : LBL_SECONDS)$==$(translate : Accounts|LBL_COPY_BILLING_ADDRESS)$+')->parse()->getContent(),
			'Record instance: Translations should be equal');
		static::$testInstanceRecord->withoutTranslations(false);
	}

	/**
	 * Testing basic source record related functions.
	 */
	public function testBasicSrcRecord()
	{
		$this->assertSame(
			'+autogenerated test lead for \App\TextParser tests+', static::$testInstanceClean->setContent('+$(sourceRecord : description)$+')->setSourceRecord(\Tests\Entity\C_RecordActions::createLeadRecord()->getId())->parse()->getContent(),
			'Clean instance: Translations should be equal');

		$this->assertSame(
			'+autogenerated test lead for \App\TextParser tests+',
			static::$testInstanceRecord->setContent('+$(sourceRecord : description)$+')->setSourceRecord(\Tests\Entity\C_RecordActions::createLeadRecord()->getId())->parse()->getContent(),
			'Record instance: Translations should be equal');
	}
}