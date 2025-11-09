<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use PDOException;

class MenuRepository
{
    private string $driver;

    public function __construct(private PDO $db)
    {
        $this->driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM menus ORDER BY location ASC');
        $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $menu) => $this->hydrateMenu($menu), $menus);
    }

    public function getWithItems(int $menuId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM menus WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $menuId]);
        $menu = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$menu) {
            return null;
        }

        $menu['items'] = $this->getItems((int) $menu['id']);

        return $this->hydrateMenu($menu);
    }

    public function getByLocation(string $location): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM menus WHERE location = :location LIMIT 1');
        $stmt->execute([':location' => $location]);
        $menu = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$menu) {
            return null;
        }

        $menu['items'] = $this->getItems((int) $menu['id']);

        return $this->hydrateMenu($menu);
    }

    public function getMenusWithItems(): array
    {
        $menus = $this->all();

        return array_map(function (array $menu): array {
            $menu['items'] = $this->getItems((int) $menu['id']);
            return $menu;
        }, $menus);
    }

    public function create(array $data): array
    {
        $name = trim($data['name'] ?? '');
        $location = trim($data['location'] ?? '');

        if ($name === '' || $location === '') {
            throw new InvalidArgumentException('Menü adı ve konumu zorunludur.');
        }

        $now = new DateTimeImmutable();
        $stmt = $this->db->prepare('INSERT INTO menus (name, location, created_at, updated_at)
            VALUES (:name, :location, :created_at, :updated_at)');

        try {
            $stmt->execute([
                ':name' => $name,
                ':location' => $location,
                ':created_at' => $now->format('Y-m-d H:i:s'),
                ':updated_at' => $now->format('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $exception) {
            throw new InvalidArgumentException('Menü oluşturulamadı: ' . $exception->getMessage(), previous: $exception);
        }

        return $this->getWithItems((int) $this->db->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $menu = $this->getWithItems($id);
        if (!$menu) {
            return null;
        }

        $name = trim($data['name'] ?? $menu['name']);
        $location = trim($data['location'] ?? $menu['location']);

        $stmt = $this->db->prepare('UPDATE menus SET name = :name, location = :location, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':location' => $location,
            ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        return $this->getWithItems($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM menus WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function replaceItems(int $menuId, array $items): array
    {
        $menu = $this->getWithItems($menuId);
        if (!$menu) {
            throw new InvalidArgumentException('Menü bulunamadı.');
        }

        $this->db->beginTransaction();
        try {
            $delete = $this->db->prepare('DELETE FROM menu_items WHERE menu_id = :menu_id');
            $delete->execute([':menu_id' => $menuId]);

            $insert = $this->db->prepare('INSERT INTO menu_items (menu_id, label, url, target, sort_order, created_at, updated_at)
                VALUES (:menu_id, :label, :url, :target, :sort_order, :created_at, :updated_at)');

            $now = new DateTimeImmutable();
            $timestamp = $now->format('Y-m-d H:i:s');
            foreach ($items as $index => $item) {
                $label = trim((string) ($item['label'] ?? ''));
                $url = trim((string) ($item['url'] ?? ''));
                if ($label === '' || $url === '') {
                    continue;
                }

                $target = in_array($item['target'] ?? '_self', ['_self', '_blank'], true)
                    ? $item['target']
                    : '_self';

                $order = isset($item['sort_order']) && $item['sort_order'] !== ''
                    ? (int) $item['sort_order']
                    : ($index + 1);

                $insert->execute([
                    ':menu_id' => $menuId,
                    ':label' => $label,
                    ':url' => $url,
                    ':target' => $target,
                    ':sort_order' => $order,
                    ':created_at' => $timestamp,
                    ':updated_at' => $timestamp,
                ]);
            }

            $this->db->commit();
        } catch (PDOException $exception) {
            $this->db->rollBack();
            throw new InvalidArgumentException('Menü öğeleri kaydedilemedi: ' . $exception->getMessage(), previous: $exception);
        }

        return $this->getWithItems($menuId) ?? [];
    }

    private function getItems(int $menuId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM menu_items WHERE menu_id = :menu_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute([':menu_id' => $menuId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $item) => $this->hydrateItem($item), $items);
    }

    private function hydrateMenu(array $menu): array
    {
        $menu['id'] = (int) $menu['id'];
        $menu['name'] = (string) $menu['name'];
        $menu['location'] = (string) $menu['location'];
        $menu['created_at'] = $menu['created_at'] ?? null;
        $menu['updated_at'] = $menu['updated_at'] ?? null;
        $menu['items'] = $menu['items'] ?? [];

        return $menu;
    }

    private function hydrateItem(array $item): array
    {
        $item['id'] = (int) $item['id'];
        $item['menu_id'] = (int) $item['menu_id'];
        $item['label'] = (string) $item['label'];
        $item['url'] = (string) $item['url'];
        $item['target'] = $item['target'] ?: '_self';
        $item['sort_order'] = (int) $item['sort_order'];
        $item['created_at'] = $item['created_at'] ?? null;
        $item['updated_at'] = $item['updated_at'] ?? null;

        return $item;
    }
}
