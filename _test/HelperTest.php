<?php


namespace dokuwiki\plugin\acknowledge\test;

use DokuWikiTest;

/**
 * Helper tests for the acknowledge plugin
 *
 * @group plugin_acknowledge
 * @group plugins
 */
class HelperTest extends DokuWikiTest
{
    /** @var array */
    protected $pluginsEnabled = ['acknowledge', 'sqlite'];
    /** @var \helper_plugin_acknowledge $helper */
    protected $helper;
    /** @var \helper_plugin_sqlite */
    protected $db;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        /** @var \auth_plugin_authplain $auth */
        global $auth;
        $auth->createUser('max', 'none', 'max', 'max@example.com', ['super']);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->helper = plugin_load('helper', 'acknowledge');

        $this->db = $this->helper->getDB();

        $pages = "REPLACE INTO pages(page,lastmod)
            VALUES ('dokuwiki:acktest1', 1560805365),
            ('dokuwiki:acktest2', 1560805365),
            ('dokuwiki:acktest3', 1560805365)";
        $this->db->query($pages);

        $assignments = "REPLACE INTO assignments(page,pageassignees)
            VALUES ('dokuwiki:acktest1', 'regular, @super'),
            ('dokuwiki:acktest2', '@super'),
            ('dokuwiki:acktest3', '@user')";
        $this->db->query($assignments);

        $acks = "REPLACE INTO acks(page,user,ack)
            VALUES ('dokuwiki:acktest3', 'regular', 1550801270),
            ('dokuwiki:acktest3', 'regular', 1560805555),
            ('dokuwiki:acktest1', 'max', 1550805770),
            ('dokuwiki:acktest1', 'max', 1560805770),
            ('dokuwiki:acktest3', 'max', 1560805000)
            ";
        $this->db->query($acks);
    }

    /**
     * test latest acknowledgements
     */
    public function test_getLatestAcknowledgements()
    {
        $actual = $this->helper->getAcknowledgements();
        $expected = [
            [
                'page' => 'dokuwiki:acktest1',
                'user' => 'max',
                'ack' => '1560805770',
                'lastmod' => '1560805365',
            ],
            [
                'page' => 'dokuwiki:acktest3',
                'user' => 'regular',
                'ack' => '1560805555',
                'lastmod' => '1560805365',
            ],
            [
                'page' => 'dokuwiki:acktest3',
                'user' => 'max',
                'ack' => '1560805000',
                'lastmod' => '1560805365',
            ],
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * test latest acknowledgements limited to 1
     */
    public function test_getLimitedAcknowledgements()
    {
        $actual = $this->helper->getAcknowledgements(1);
        $expected = [
            [
                'page' => 'dokuwiki:acktest1',
                'user' => 'max',
                'ack' => '1560805770',
                'lastmod' => '1560805365',
            ],
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * test assignment query
     */
    public function test_getUserAssignments()
    {
        $actual = $this->helper->getUserAssignments('regular', ['user']);
        $expected = [
            [
                'page' => 'dokuwiki:acktest1',
                'pageassignees' => 'regular, @super',
                'autoassignees' => '',
                'lastmod' => '1560805365',
                'user' => null,
                'ack' => null,
            ],
        ];
        $this->assertEquals($expected, $actual);

        $actual = $this->helper->getUserAssignments('max', ['user', 'super']);
        $expected = [
            [
                'page' => 'dokuwiki:acktest2',
                'pageassignees' => '@super',
                'autoassignees' => '',
                'lastmod' => '1560805365',
                'user' => null,
                'ack' => null,
            ],
            [
                'page' => 'dokuwiki:acktest3',
                'pageassignees' => '@user',
                'autoassignees' => '',
                'lastmod' => '1560805365',
                'user' => null,
                'ack' => null,
            ],
        ];
        $this->assertEquals($expected, $actual);
    }

    public function test_getUserAcknowledgements()
    {
        $actual = $this->helper->getUserAcknowledgements('max', ['user', 'super']);
        $expected = [
            [
                'page' => 'dokuwiki:acktest1',
                'pageassignees' => 'regular, @super',
                'autoassignees' => '',
                'lastmod' => '1560805365',
                'user' => 'max',
                'ack' => '1560805770',
            ],
            [
                'page' => 'dokuwiki:acktest2',
                'pageassignees' => '@super',
                'autoassignees' => '',
                'lastmod' => '1560805365',
                'user' => null,
                'ack' => null,
            ],
            [
                'page' => 'dokuwiki:acktest3',
                'pageassignees' => '@user',
                'autoassignees' => '',
                'lastmod' => '1560805365',
                'user' => 'max',
                'ack' => '1560805000',
            ],
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Check what users are assigned to a page that has a user and a group in the database
     */
    public function test_getPageAssignees()
    {
        $actual = $this->helper->getPageAssignees('dokuwiki:acktest1');
        $expected = ['regular', 'max'];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Check what acknowledgments are there for a page
     */
    public function test_getPageAcknowledgements()
    {
        $actual = $this->helper->getPageAcknowledgements('dokuwiki:acktest1');
        $expected = [
            [
                'page' => 'dokuwiki:acktest1',
                'lastmod' => '1560805365',
                'user' => 'max',
                'ack' => '1560805770',
            ],
            [
                'page' => 'dokuwiki:acktest1',
                'lastmod' => '1560805365',
                'user' => 'regular',
                'ack' => null,
            ],

        ];
        $this->assertEquals($expected, $actual);

    }
}
