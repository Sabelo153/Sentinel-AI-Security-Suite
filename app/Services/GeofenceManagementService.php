<?php

declare(strict_types=1);

namespace Sentinel\Services;

use PDOException;
use Sentinel\Core\HttpException;
use Sentinel\Repositories\GeofenceRepository;

final class GeofenceManagementService
{
	private const SHAPES = ['CIRCLE', 'POLYGON'];
	private const STATUSES = ['ACTIVE', 'INACTIVE', 'ARCHIVED'];

	public function __construct(private readonly GeofenceRepository $geofences) {}
	public function listGeofences(): array { try { return $this->geofences->listForCompany(); } catch (PDOException $e) { throw $this->unavailable($e); } }
	public function getGeofence(string $id): array { try { $geofence = $this->geofences->findByIdForCompany($id); } catch (PDOException $e) { throw $this->unavailable($e); } if ($geofence === null) throw HttpException::notFound('Geofence not found.', 'geofence_not_found'); return $geofence; }
	public function createGeofence(array $input): array { $data = $this->validate($input, false); try { $this->references($data); if ($this->geofences->codeExists($data['geofence_code'])) throw HttpException::conflict('Geofence code already exists.', 'geofence_code_conflict'); return $this->geofences->createForCompany($data); } catch (HttpException $e) { throw $e; } catch (PDOException $e) { throw $this->unavailable($e); } }
	public function updateGeofence(string $id, array $input): array { $existing = $this->getGeofence($id); $editable = array_flip(['site_id', 'geofence_code', 'geofence_name', 'geofence_shape', 'center_latitude', 'center_longitude', 'radius_meters', 'polygon_json', 'geofence_status']); if (array_diff_key($input, $editable)) throw HttpException::unprocessable('Invalid geofence fields.'); $effective = $this->validate(array_merge(array_intersect_key($existing, $editable), $input), true); try { $this->references($effective); if (isset($input['geofence_code']) && $this->geofences->codeExists($input['geofence_code'], $id)) throw HttpException::conflict('Geofence code already exists.', 'geofence_code_conflict'); $updated = $this->geofences->updateForCompany($id, $input); } catch (HttpException $e) { throw $e; } catch (PDOException $e) { throw $this->unavailable($e); } if ($updated === null) throw HttpException::notFound('Geofence not found.', 'geofence_not_found'); return $updated; }

	public function evaluate(string $id, float $latitude, float $longitude): bool
	{
		$geofence = $this->getGeofence($id);
		if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) throw HttpException::unprocessable('Coordinates are invalid.', 'invalid_coordinates');
		if ($geofence['geofence_shape'] === 'CIRCLE') return $this->distance($latitude, $longitude, (float) $geofence['center_latitude'], (float) $geofence['center_longitude']) <= (float) $geofence['radius_meters'];
		$polygon = json_decode((string) $geofence['polygon_json'], true);
		$points = $this->points($polygon);
		$inside = false;
		for ($i = 0, $j = count($points) - 1; $i < count($points); $j = $i++) { [$x, $y] = $points[$i]; [$previousX, $previousY] = $points[$j]; if ((($y > $latitude) !== ($previousY > $latitude)) && ($longitude < ($previousX - $x) * ($latitude - $y) / (($previousY - $y) ?: 1e-12) + $x)) $inside = !$inside; }
		return $inside;
	}

	private function validate(array $input, bool $update): array
	{
		$allowed = ['site_id', 'geofence_code', 'geofence_name', 'geofence_shape', 'center_latitude', 'center_longitude', 'radius_meters', 'polygon_json', 'geofence_status'];
		if (array_diff(array_keys($input), $allowed) !== []) throw HttpException::unprocessable('Invalid geofence fields.', 'invalid_geofence_request');
		if (!$update) foreach (['geofence_code', 'geofence_name'] as $field) if (!isset($input[$field])) throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is required.', 'invalid_' . $field);
		$data = $input;
		if (isset($data['site_id'])) $data['site_id'] = $this->positive($data['site_id'], 'site_id');
		foreach (['geofence_code' => 60, 'geofence_name' => 150] as $field => $max) if (isset($data[$field]) && (!is_string($data[$field]) || $data[$field] === '' || strlen($data[$field]) > $max)) throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field);
		$data['geofence_shape'] ??= 'CIRCLE'; $data['geofence_status'] ??= 'ACTIVE';
		if (!in_array($data['geofence_shape'], self::SHAPES, true)) throw HttpException::unprocessable('Geofence shape is invalid.', 'invalid_geofence_shape');
		if (!in_array($data['geofence_status'], self::STATUSES, true)) throw HttpException::unprocessable('Geofence status is invalid.', 'invalid_geofence_status');
		foreach (['center_latitude' => [-90, 90], 'center_longitude' => [-180, 180], 'radius_meters' => [0.01, null]] as $field => [$min, $max]) if (isset($data[$field])) $data[$field] = $this->decimal($data[$field], $field, $min, $max);
		if ($data['geofence_shape'] === 'CIRCLE') { foreach (['center_latitude', 'center_longitude', 'radius_meters'] as $field) if (!array_key_exists($field, $data) || $data[$field] === null) throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is required for a circle.', 'invalid_' . $field); }
		if ($data['geofence_shape'] === 'POLYGON') { if (!array_key_exists('polygon_json', $data) || $this->points(json_decode((string) $data['polygon_json'], true)) === []) throw HttpException::unprocessable('Polygon is invalid.', 'invalid_polygon_json'); }
		return $data;
	}

	private function references(array $data): void { if (isset($data['site_id']) && !$this->geofences->siteBelongsToCompany((string) $data['site_id'])) throw HttpException::notFound('Site not found.', 'site_not_found'); }
	private function points(mixed $polygon): array { if (!is_array($polygon) || count($polygon) < 3) throw HttpException::unprocessable('Polygon is invalid.', 'invalid_polygon_json'); $points = []; foreach ($polygon as $point) { if (is_array($point) && isset($point['latitude'], $point['longitude'])) $points[] = [(float) $point['longitude'], (float) $point['latitude']]; elseif (is_array($point) && count($point) >= 2 && is_numeric($point[0]) && is_numeric($point[1])) $points[] = [(float) $point[0], (float) $point[1]]; else throw HttpException::unprocessable('Polygon is invalid.', 'invalid_polygon_json'); } return $points; }
	private function positive(mixed $value, string $field): int { if ((!is_int($value) && !(is_string($value) && ctype_digit($value))) || (int) $value < 1) throw HttpException::unprocessable(ucfirst($field) . ' is invalid.', 'invalid_' . $field); return (int) $value; }
	private function decimal(mixed $value, string $field, float $min, ?float $max): float { if (!is_numeric($value) || (float) $value < $min || ($max !== null && (float) $value > $max)) throw HttpException::unprocessable(ucfirst(str_replace('_', ' ', $field)) . ' is invalid.', 'invalid_' . $field); return (float) $value; }
	private function distance(float $lat1, float $lon1, float $lat2, float $lon2): float { $earth = 6371000; $dLat = deg2rad($lat2 - $lat1); $dLon = deg2rad($lon2 - $lon1); $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2; return $earth * 2 * asin(min(1, sqrt($a))); }
	private function unavailable(PDOException $e): HttpException { return new HttpException(503, 'Geofence service unavailable.', 'geofence_service_unavailable', $e); }
}
