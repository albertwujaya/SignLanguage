<?php

namespace Tests\Feature;

use App\Http\Requests\Admin\GestureRequest;
use App\Models\Gesture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGestureCategoriesAndTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_gesture_request_rejects_invalid_level_and_category_combinations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Abjad hanya boleh alpha
        $this->actingAs($admin)
            ->post('/admin/gestures', [
                'slug' => 'test-alpha-wrong',
                'word' => 'Test',
                'category' => 'greet',
                'level' => 'abjad',
                'difficulty' => 'Easy',
                'description' => 'Test description',
            ])
            ->assertSessionHasErrors(['category']);

        // Angka hanya boleh num
        $this->actingAs($admin)
            ->post('/admin/gestures', [
                'slug' => 'test-num-wrong',
                'word' => 'Test',
                'category' => 'days',
                'level' => 'angka',
                'difficulty' => 'Easy',
                'description' => 'Test description',
            ])
            ->assertSessionHasErrors(['category']);

        // Kata boleh days dan verbs
        $this->actingAs($admin)
            ->post('/admin/gestures', [
                'slug' => 'senin-test',
                'word' => 'MONDAY',
                'indonesian' => 'SENIN',
                'category' => 'days',
                'level' => 'kata',
                'difficulty' => 'Easy',
                'description' => 'Test description hari senin',
            ])
            ->assertRedirectToRoute('admin.gestures.index');

        $this->assertDatabaseHas('gestures', [
            'slug' => 'senin-test',
            'category' => 'days',
            'level' => 'kata',
        ]);
    }

    public function test_categories_constant_contains_days_and_verbs(): void
    {
        $this->assertContains('days', GestureRequest::CATEGORIES);
        $this->assertContains('verbs', GestureRequest::CATEGORIES);
        $this->assertNotContains('emerg', GestureRequest::CATEGORIES);
        $this->assertNotContains('needs', GestureRequest::CATEGORIES);
    }

    public function test_admin_gestures_index_can_filter_by_level(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Gesture::create([
            'slug' => 'kata-1',
            'word' => 'KATA',
            'category' => 'greet',
            'level' => 'kata',
            'difficulty' => 'Easy',
            'description' => 'Desc kata',
            'status' => 'published',
        ]);

        Gesture::create([
            'slug' => 'abjad-1',
            'word' => 'A',
            'category' => 'alpha',
            'level' => 'abjad',
            'difficulty' => 'Easy',
            'description' => 'Desc abjad',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get('/admin/gestures?level=abjad')
            ->assertOk()
            ->assertSee('Alfabet')
            ->assertSee('abjad-1')
            ->assertDontSee('kata-1');
    }
}
