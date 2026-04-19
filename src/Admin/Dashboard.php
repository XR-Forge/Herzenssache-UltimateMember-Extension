<?php
/**
 * Admin Dashboard for Herzenssache UltimateMember REST API
 *
 * @package Herzenssache\UltimateMember
 */

namespace Herzenssache\UltimateMember\Admin;

use Herzenssache\UltimateMember\Repository\UserRepository;
use Herzenssache\UltimateMember\Repository\FormRepository;
use Herzenssache\UltimateMember\Repository\RoleRepository;

/**
 * Main admin dashboard class
 */
class Dashboard {

	/**
	 * Singleton instance
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Get singleton instance
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Private constructor to prevent direct instantiation
	}

	/**
	 * Render the admin dashboard
	 *
	 * @return void
	 */
	public function render() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'UltimateMember REST API Dashboard', 'herzenssache-um' ); ?></h1>

			<div class="hzs-um-dashboard">
				<?php $this->render_stats(); ?>
				<?php $this->render_recent_users(); ?>
				<?php $this->render_recent_submissions(); ?>
				<?php $this->render_roles(); ?>
			</div>
		</div>

		<style>
			.hzs-um-dashboard {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
				gap: 20px;
				margin-top: 20px;
			}

			.hzs-um-widget {
				background: #fff;
				border: 1px solid #ccc;
				border-radius: 4px;
				padding: 20px;
				box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
			}

			.hzs-um-widget h2 {
				margin-top: 0;
				font-size: 16px;
				font-weight: 600;
				border-bottom: 2px solid #0073aa;
				padding-bottom: 10px;
			}

			.hzs-um-widget table {
				width: 100%;
				border-collapse: collapse;
			}

			.hzs-um-widget th,
			.hzs-um-widget td {
				text-align: left;
				padding: 8px;
				border-bottom: 1px solid #eee;
			}

			.hzs-um-widget th {
				font-weight: 600;
				background: #f5f5f5;
			}

			.hzs-um-stat {
				text-align: center;
				padding: 20px;
				border-bottom: 1px solid #eee;
			}

			.hzs-um-stat:last-child {
				border-bottom: none;
			}

			.hzs-um-stat-number {
				font-size: 32px;
				font-weight: 600;
				color: #0073aa;
			}

			.hzs-um-stat-label {
				font-size: 12px;
				color: #666;
				text-transform: uppercase;
				margin-top: 5px;
			}

			.hzs-um-empty {
				text-align: center;
				padding: 20px;
				color: #999;
			}
		</style>
		<?php
	}

	/**
	 * Render statistics widget
	 *
	 * @return void
	 */
	private function render_stats() {
		$users = UserRepository::get_users( 1, 1 );
		$forms = FormRepository::get_forms();
		$roles = RoleRepository::get_roles();

		?>
		<div class="hzs-um-widget">
			<h2><?php esc_html_e( 'Statistics', 'herzenssache-um' ); ?></h2>
			<div class="hzs-um-stat">
				<div class="hzs-um-stat-number"><?php echo (int) $users['total']; ?></div>
				<div class="hzs-um-stat-label"><?php esc_html_e( 'Total Users', 'herzenssache-um' ); ?></div>
			</div>
			<div class="hzs-um-stat">
				<div class="hzs-um-stat-number"><?php echo count( $forms ); ?></div>
				<div class="hzs-um-stat-label"><?php esc_html_e( 'Forms', 'herzenssache-um' ); ?></div>
			</div>
			<div class="hzs-um-stat">
				<div class="hzs-um-stat-number"><?php echo count( $roles ); ?></div>
				<div class="hzs-um-stat-label"><?php esc_html_e( 'Roles', 'herzenssache-um' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render recent users widget
	 *
	 * @return void
	 */
	private function render_recent_users() {
		$users_data = UserRepository::get_users( 1, 5 );
		$users = $users_data['users'];

		?>
		<div class="hzs-um-widget">
			<h2><?php esc_html_e( 'Recent Users', 'herzenssache-um' ); ?></h2>
			<?php if ( ! empty( $users ) ) : ?>
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Username', 'herzenssache-um' ); ?></th>
							<th><?php esc_html_e( 'Email', 'herzenssache-um' ); ?></th>
							<th><?php esc_html_e( 'Status', 'herzenssache-um' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $users as $user ) : ?>
							<tr>
								<td><?php echo esc_html( $user['username'] ); ?></td>
								<td><?php echo esc_html( $user['email'] ); ?></td>
								<td><span class="status status-<?php echo esc_attr( $user['status'] ); ?>"><?php echo esc_html( $user['status'] ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="hzs-um-empty"><?php esc_html_e( 'No users found', 'herzenssache-um' ); ?></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render recent submissions widget
	 *
	 * @return void
	 */
	private function render_recent_submissions() {
		// Get recent submissions from all forms
		$forms = FormRepository::get_forms();
		$all_submissions = array();

		foreach ( $forms as $form ) {
			$submissions_data = FormRepository::get_submissions( $form['id'], 1, 100 );
			if ( ! is_wp_error( $submissions_data ) && ! empty( $submissions_data['submissions'] ) ) {
				$all_submissions = array_merge( $all_submissions, $submissions_data['submissions'] );
			}
		}

		// Sort by created_at and get the last 5
		usort(
			$all_submissions,
			function( $a, $b ) {
				return strtotime( $b['created_at'] ) - strtotime( $a['created_at'] );
			}
		);
		$recent = array_slice( $all_submissions, 0, 5 );

		?>
		<div class="hzs-um-widget">
			<h2><?php esc_html_e( 'Recent Submissions', 'herzenssache-um' ); ?></h2>
			<?php if ( ! empty( $recent ) ) : ?>
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Form', 'herzenssache-um' ); ?></th>
							<th><?php esc_html_e( 'Status', 'herzenssache-um' ); ?></th>
							<th><?php esc_html_e( 'Date', 'herzenssache-um' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent as $submission ) : ?>
							<tr>
								<td>#<?php echo (int) $submission['form_id']; ?></td>
								<td><?php echo esc_html( $submission['status'] ); ?></td>
								<td><?php echo esc_html( wp_date( 'M d, Y H:i', strtotime( $submission['created_at'] ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="hzs-um-empty"><?php esc_html_e( 'No submissions yet', 'herzenssache-um' ); ?></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render roles widget
	 *
	 * @return void
	 */
	private function render_roles() {
		$roles = RoleRepository::get_roles();

		?>
		<div class="hzs-um-widget">
			<h2><?php esc_html_e( 'Available Roles', 'herzenssache-um' ); ?></h2>
			<?php if ( ! empty( $roles ) ) : ?>
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Role', 'herzenssache-um' ); ?></th>
							<th><?php esc_html_e( 'Capabilities', 'herzenssache-um' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_slice( $roles, 0, 5 ) as $role ) : ?>
							<tr>
								<td><?php echo esc_html( $role['label'] ); ?></td>
								<td><?php echo count( $role['capabilities'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="hzs-um-empty"><?php esc_html_e( 'No roles found', 'herzenssache-um' ); ?></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
