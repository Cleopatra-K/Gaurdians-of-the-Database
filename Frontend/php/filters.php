<?php
function getFilteredSortedProducts($filters = [], $sortBy = "") {
    require 'config.php'; 

    $where = [];
    $params = [];
    if (!empty($filters['brand'])) {
        $where[] = "brand = ?";
        $params[] = $filters['brand'];
    }
    if (!empty($filters['category'])) {
        $where[] = "categories LIKE ?";
        $params[] = '%' . $filters['category'] . '%';
    }
    if (!empty($filters['distributor'])) {
        $where[] = "distributor = ?";
        $params[] = $filters['distributor'];
    }

    if (isset($filters['availability'])) {
        $where[] = "availability = ?";
        $params[] = $filters['availability'] ? 1 : 0;
    }

    $sql = "SELECT * FROM products";
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    if ($sortBy === "title-desc") {
        $sql .= " ORDER BY title DESC";
    } elseif ($sortBy === "brand") {
        $sql .= " ORDER BY brand ASC";
    } elseif ($sortBy === "category") {
        $sql .= " ORDER BY categories ASC";
    } elseif ($sortBy === "distributor") {
        $sql .= " ORDER BY distributor ASC";
    } elseif ($sortBy === "availability") {
        $sql .= " ORDER BY availability DESC";
    }

    $stmt = $conn->prepare($sql);
    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    return $products;
}

$products = getFilteredSortedProducts($filters, "title-desc");