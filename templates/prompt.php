<?php

use SimpleSAML\Utils\HTTP;

$this->includeAtTemplateBase('includes/header.php');
$attributeName = $this->data['attributeName'];
$defaultHeader = $this->t('{cirrusgeneral:prompt:header_attribute_default}');
$defaultText = $this->t('{cirrusgeneral:prompt:text_attribute_single}');

$promptHeader = $this->t("{cirrusgeneral:prompt:header_attribute_$attributeName}") ?? $defaultHeader;
$promptText = $this->t("{cirrusgeneral:prompt:text_attribute_$attributeName}") ?? $defaultText
?>
    <div class="container">
        <h2><?php echo htmlspecialchars($promptHeader); ?></h2>
        <p><?php echo htmlspecialchars($promptText); ?></p>
        <?php
        if (array_key_exists('errorMessage', $this->data)) {
            ?>
            <div class="alert alert-danger">
                <strong>Error</strong> <?php echo htmlspecialchars($this->data['errorMessage']); ?>
            </div>
            <?php
        }
        ?>
        <ul class="list-group">
            <?php
            foreach ($this->data['attributeValues'] as $value) {
                $label = $value;
                if (array_key_exists($value, $this->data['attributeLabels'])) {
                    $label = $this->data['attributeLabels'][$value] . ' ' . $value;
                }
                $label = htmlspecialchars($label);
                $url = HTTP::addURLParameters(HTTP::getSelfURL(), ['name' => $attributeName, 'value' => $value]);
                ?>
                <li class="list-group-item"><a href="<?php echo $url; ?>"><?php echo $label; ?></a></li>
                <?php
            }
            ?>
        </ul>
    </div>

<?php
$this->includeAtTemplateBase('includes/footer.php');
