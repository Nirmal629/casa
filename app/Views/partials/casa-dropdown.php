<?php
$id          = $id ?? ($dropdown['id'] ?? ('casa_dd_' . uniqid()));
$name        = $name ?? ($dropdown['name'] ?? $id);
$selectedValue = $value ?? ($dropdown['value'] ?? '');
$optionsList = $options ?? ($dropdown['options'] ?? []);
$size        = $size ?? ($dropdown['size'] ?? 'md');
$icon        = $icon ?? ($dropdown['icon'] ?? '');
$placeholder = $placeholder ?? ($dropdown['placeholder'] ?? 'Select...');
$class       = $class ?? ($dropdown['class'] ?? '');
$searchable  = !empty($searchable ?? ($dropdown['searchable'] ?? false));
$align       = $align ?? ($dropdown['align'] ?? 'left');
$extraAttrs  = $extraAttrs ?? ($dropdown['extraAttrs'] ?? '');
?>
<select
    id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
    name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
    class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>"
    data-casa-dropdown
    data-size="<?= htmlspecialchars($size, ENT_QUOTES, 'UTF-8') ?>"
    data-align="<?= htmlspecialchars($align, ENT_QUOTES, 'UTF-8') ?>"
    <?= !empty($icon) ? 'data-icon="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
    <?= !empty($placeholder) ? 'data-placeholder="' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
    <?= $searchable ? 'data-searchable="true"' : '' ?>
    <?= $extraAttrs ?>
>
    <?php foreach ($optionsList as $optKey => $optVal): ?>
        <?php
        if (is_array($optVal)) {
            $val = $optVal['value'] ?? $optKey;
            $lbl = $optVal['label'] ?? ($optVal['name'] ?? $val);
            $optIcon = $optVal['icon'] ?? '';
            $optBadge = $optVal['badge'] ?? '';
            $optRight = $optVal['right'] ?? '';
            $disabled = !empty($optVal['disabled']) ? 'disabled' : '';
        } else {
            $val = $optKey;
            $lbl = $optVal;
            $optIcon = '';
            $optBadge = '';
            $optRight = '';
            $disabled = '';
        }
        $isSelected = (string)$val === (string)$selectedValue ? 'selected' : '';
        $iconAttr = !empty($optIcon) ? 'data-icon="' . htmlspecialchars($optIcon, ENT_QUOTES, 'UTF-8') . '"' : '';
        $badgeAttr = !empty($optBadge) ? 'data-badge="' . htmlspecialchars($optBadge, ENT_QUOTES, 'UTF-8') . '"' : '';
        $rightAttr = !empty($optRight) ? 'data-right-text="' . htmlspecialchars($optRight, ENT_QUOTES, 'UTF-8') . '"' : '';
        ?>
        <option value="<?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?>" <?= $isSelected ?> <?= $disabled ?> <?= $iconAttr ?> <?= $badgeAttr ?> <?= $rightAttr ?>>
            <?= htmlspecialchars((string)$lbl, ENT_QUOTES, 'UTF-8') ?>
        </option>
    <?php endforeach; ?>
</select>
