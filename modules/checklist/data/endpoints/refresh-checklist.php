<?php
namespace Elementor\Modules\Search\Data\Endpoints;

use Elementor\Core\Editor\Data\Globals\Endpoints\Base;
use Elementor\Modules\Checklist\Module as Checklist_Module;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Refresh_Checklist extends Base {

	public function get_name() : string {
		return 'refresh-checklist';
	}


	public function register_routes() {
		register_rest_route( 'elementor/v1', '/refresh-checklist', [
			'methods' => 'GET',
			'callback' => [ $this, 'get_checklist_data' ],
		] );
	}

	public function get_route() : string {
		return 'checklist';
	}

	public function get_checklist_data( \WP_REST_Request $request ): array {
		$data = $request->get_params();
		$checklist_module = Plugin::elementor()->modules_manager->get_modules( 'checklist' );
		$steps_data = $checklist_module->get_step_manager()->get_steps_for_frontend();

		return [
			'data' => wp_json_encode( $steps_data ),
		];
	}
}
