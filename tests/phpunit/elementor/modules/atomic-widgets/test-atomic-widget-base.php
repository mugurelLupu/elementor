<?php

namespace Elementor\Testing\Modules\AtomicWidgets;

use Elementor\Modules\AtomicWidgets\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Select_Control;
use Elementor\Modules\AtomicWidgets\Controls\Types\Textarea_Control;
use Elementor\Modules\AtomicWidgets\Schema\Atomic_Prop;
use Elementor\Testing\Modules\AtomicWidgets\Mocks\Mock_Widget_A;
use Elementor\Testing\Modules\AtomicWidgets\Mocks\Mock_Widget_B;
use ElementorEditorTesting\Elementor_Test_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/mocks/mock-widget-a.php';
require_once __DIR__ . '/mocks/mock-widget-b.php';

class Test_Atomic_Widget_Base extends Elementor_Test_Base {

	public function test_get_atomic_settings__returns_the_saved_value() {
		// Arrange.
		$widget = new Mock_Widget_A( [
			'id' => 1,
			'settings' => [
				'test_prop_a' => 'saved-value',
			],
		] );

		// Act.
		$settings = $widget->get_atomic_settings();

		// Assert.
		$this->assertEquals( [
			'test_prop_a' => 'saved-value',
		], $settings );
	}

	public function test_get_atomic_settings__returns_the_default_value() {
		// Arrange.
		$widget = new Mock_Widget_A( [
			'id' => 1,
			'settings' => [],
		] );

		// Act.
		$settings = $widget->get_atomic_settings();

		// Assert.
		$this->assertEquals( [
			'test_prop_a' => 'default-value-a',
		], $settings );
	}

	public function test_get_atomic_settings__returns_only_settings_that_are_defined_in_the_schema() {
		// Arrange.
		$widget = new Mock_Widget_A( [
			'id' => 1,
			'settings' => [
				'test_prop_a' => 'saved-value',
				'not_in_schema' => 'not-in-schema',
			],
		] );

		// Act.
		$settings = $widget->get_atomic_settings();

		// Assert.
		$this->assertEquals( [
			'test_prop_a' => 'saved-value',
		], $settings );
	}

	public function test_get_props_schema__is_serializable() {
		// Act.
		$serialized = json_encode( Mock_Widget_A::get_props_schema() );

		// Assert.
		$this->assertJsonStringEqualsJsonString( '{
			"test_prop_a": {
				"default": "default-value-a"
			}
		}', $serialized );
	}

	public function test_get_atomic_controls__throws_when_control_is_invalid() {
		// Arrange.
		$widget = $this->make_mock_widget( [
			'props_schema' => [],
			'controls' => [
				new \stdClass(),
			],
		] );

		// Expect.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Control must be an instance of `Atomic_Control_Base`.' );

		// Act.
		$widget->get_atomic_controls();
	}

	public function test_get_atomic_controls__throws_when_control_inside_a_section_is_not_in_schema() {
		// Arrange.
		$widget = $this->make_mock_widget( [
			'props_schema' => [],
			'controls' => [
				Section::make()->set_items( [
					Textarea_Control::bind_to( 'not-in-schema' )
				] )
			],
		] );

		// Expect.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Prop `not-in-schema` is not defined in the schema of `test-widget`. Did you forget to define it?' );

		// Act.
		$widget->get_atomic_controls();
	}

	public function test_get_atomic_controls__throws_when_top_level_control_is_not_in_schema() {
		// Arrange.
		$widget = $this->make_mock_widget( [
			'props_schema' => [],
			'controls' => [
				Textarea_Control::bind_to( 'not-in-schema' ),
			],
		] );

		// Expect.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Prop `not-in-schema` is not defined in the schema of `test-widget`. Did you forget to define it?' );

		// Act.
		$widget->get_atomic_controls();
	}

	public function test_get_atomic_controls__throws_when_control_has_empty_bind() {
		// Arrange.
		$widget = $this->make_mock_widget( [
			'props_schema' => [],
			'controls' => [
				Textarea_Control::bind_to( '' ),
			],
		] );

		// Expect.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Control is missing a bound prop from the schema.' );

		// Act.
		$widget->get_atomic_controls();
	}

	public function test_get_atomic_controls() {
		// Arrange.
		$controls_definitions = [
			// Top-level control
			Textarea_Control::bind_to( 'text' ),

			// Control in section
			Section::make()->set_items( [
				Select_Control::bind_to( 'select' ),

				// Nested section
				Section::make()->set_items( [
					Textarea_Control::bind_to( 'nested-text' ),
				] ),
			] ),
		];

		$widget = $this->make_mock_widget( [
			'props_schema' => [
				'text' => Atomic_Prop::make(),
				'select' => Atomic_Prop::make(),
				'nested-text' => Atomic_Prop::make(),
			],
			'controls' => $controls_definitions,
		] );

		// Act.
		$controls = $widget->get_atomic_controls();

		// Assert.
		$this->assertEquals( $controls_definitions, $controls );
	}

	/**
	 * @param array{controls: array, props_schema: array} $options
	 */
	private function make_mock_widget( array $options ) {
		return new class( $options ) extends Atomic_Widget_Base {
			private static array $options;

			public function __construct( $options ) {
				static::$options = $options;

				parent::__construct( [], [] );
			}

			public function get_name() {
				return 'test-widget';
			}

			protected function define_atomic_controls(): array {
				return static::$options['controls'];
			}

			protected static function define_props_schema(): array {
				return static::$options['props_schema'];
			}
		};
	}
}
