<?php
/**
*
* @package Board3 Portal Lottery Module
* @copyright (c) 2019 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\b3plottery;

/**
* @package B3P Lottery Module
*/
class b3plottery extends \board3\portal\modules\module_base
{
	/**
	* Allowed columns: Just sum up your options (Exp: left + right = 10)
	* top		1
	* left		2
	* center	4
	* right		8
	* bottom	16
	*/
	public $columns = 10;

	/**
	* Default modulename
	*/
	public $name = 'LOTTERY_TITLE';

	/**
	* Default module-image:
	* file must be in "{T_THEME_PATH}/images/portal/"
	*/
	public $image_src = '';

	/**
	* module-language file
	* file must be in "language/{$user->lang}/portal/"
	*/
	public $language = array(
		'vendor'	=> 'dmzx/b3plottery',
		'file'		=> 'b3plottery',
	);

	protected $config, $db, $template, $user, $table_prefix, $helper;

	public function __construct(
		$config,
		$db,
		$template,
		$user,
		$table_prefix,
		$helper
	)
	{
		$this->config 				= $config;
		$this->db 					= $db;
		$this->template 			= $template;
		$this->user 				= $user;
		$this->table_prefix 		= $table_prefix;
		$this->helper 				= $helper;
	}

	public function get_template_side($module_id)
	{
		// Set variables
		$no_of_tickets = $no_of_players = $last_winner = $last_winner_id = '';

		// Read out the config data
		$sql_array = array(
			'SELECT'	=> 'config_name, config_value',
			'FROM'		=> array(
				$this->table_prefix . 'points_config' => 'c',
			),
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$points_config[$row['config_name']] = $row['config_value'];
		}
		$this->db->sql_freeresult($result);

		// Read out values data
		$sql_array = array(
			'SELECT'	=> '*',
			'FROM'		=> array(
				$this->table_prefix . 'points_values' => 'v',
			),
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$points_values = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Select the total number of tickets
		$sql_array = array(
			'SELECT'	=> 'COUNT(ticket_id) AS number_of_tickets',
			'FROM'		=> array(
				$this->table_prefix . 'points_lottery_tickets' => 't',
			),
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$no_of_tickets = $this->db->sql_fetchfield('number_of_tickets');
		$this->db->sql_freeresult($result);

		// Select the total number of players
		$sql_ary = array(
			'SELECT'	=> 'user_id',
			'FROM'		=> array(
				$this->table_prefix . 'points_lottery_tickets'	 => 't',
			),
		);
		$sql = $this->db->sql_build_query('SELECT_DISTINCT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$no_of_players = 0;
		while ($row = $this->db->sql_fetchrow($result))
		{
			$no_of_players += 1;
		}
		$this->db->sql_freeresult($result);

		// Select the last winner id
		$sql_array = array(
			'SELECT'	=> 'user_id',
			'FROM'		=> array(
				$this->table_prefix . 'points_lottery_history'	=> 'h',
			),
			'ORDER_BY'	=> 'id DESC'
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, 1);
		$last_winner_id = $this->db->sql_fetchfield('user_id');
		$this->db->sql_freeresult($result);

		// Check, if a user won or nobody
		if ($last_winner_id != 0)
		{
			// Select the usernames from the user table to reflect user colors
			$sql_array = array(
				'SELECT'	=> 'u.user_id, u.username, u.user_colour, l.id',

				'FROM'		=> array(
					USERS_TABLE	=> 'u',
				),
				'LEFT_JOIN' => array(
					array(
						'FROM'	=> array($this->table_prefix . 'points_lottery_history' => 'l'),
						'ON'	=> 'u.user_id = l.user_id'
					)
				),
				'ORDER_BY'	=> 'l.id DESC'
			);
			$sql = $this->db->sql_build_query('SELECT', $sql_array);
			$result = $this->db->sql_query_limit($sql, 1);
			$row = $this->db->sql_fetchrow($result);

			$winner_name = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
		}
		else
		{
			$winner_name = $this->user->lang['LOTTERY_NO_WINNER'];
		}

		// Send everything to the template
		$this->template->assign_vars(array(
			'LAST_WINNER'			=> $winner_name,
			'NO_OF_TICKETS'			=> $no_of_tickets,
			'NO_OF_PLAYERS'			=> $no_of_players,
			'JACKPOT'				=> number_format($points_values['lottery_jackpot'], 2, ",", "."),
			'CASH_NAME'				=> $this->config['points_name'],
			'LOTTERY_NAME'			=> $points_values['lottery_name'],
			'LOTTERY_GOTO'			=> $this->user->lang('LOTTERY_GOTO', $points_values['lottery_name']),
			'NEXT_DRAWING'			=> $this->user->format_date($points_values['lottery_last_draw_time'] + $points_values['lottery_draw_period']),
			'S_DRAWING_ENABLED'		=> ($points_values['lottery_draw_period']) ? true : false,
			'S_LOTTERY_ENABLED'		=> ($points_config['lottery_enable']) ? true : false,
			'U_LOTTERY'				=> $this->helper->route('dmzx_ultimatepoints_controller', array('mode' => 'lottery')),
		));

		return '@dmzx_b3plottery/lottery_side.html';
	}

	public function get_template_acp($module_id)
	{
		return array(
			'title'	=> 'PORTAL_LOTTERY',
			'vars'	=> array(
			),
		);
	}

	public function install($module_id)
	{
		$this->config->delete('dmzx_b3plottery' . $module_id);
		return true;
	}

	public function uninstall($module_id, $db)
	{
		$this->config->delete('dmzx_b3plottery' . $module_id);
		return true;
	}
}
