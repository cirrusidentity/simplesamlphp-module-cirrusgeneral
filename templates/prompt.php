<?php

use SimpleSAML\Utils\HTTP;

$this->includeAtTemplateBase('includes/header.php');
$attributeName = $this->data['attributeName'];
$defaultHeader = $this->t('{cirrusgeneral:prompt:header_attribute_default}');
$defaultText = $this->t('{cirrusgeneral:prompt:text_attribute_single}');
$headerTag="{cirrusgeneral:prompt:header_attribute_$attributeName}";
$textTag="{cirrusgeneral:prompt:text_attribute_$attributeName}";
$promptHeader = is_null($this->getTag($headerTag)) ? $defaultHeader : $this->t($headerTag);
$promptText = is_null($this->getTag($textTag)) ? $defaultText : $this->t($textTag);
?>
    <div class="calinks-login-buttons">
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
            <?php
            foreach ($this->data['attributeValues'] as $value) {
                $label = $value;
                if (array_key_exists($value, $this->data['attributeLabels'])) {
                    $label = $this->data['displayAttributeValue'] ? 
                        $this->data['attributeLabels'][$value] . ' ' . $value 
                    : 
                        $this->data['attributeLabels'][$value];
                }
                $label = htmlspecialchars($label);
                $url = HTTP::addURLParameters(HTTP::getSelfURL(), ['name' => $attributeName, 'value' => $value]);
                ?>
                <a class="calinks-button btn btn-default btn-lg btn-block" href="<?php echo $url; ?>"><?php echo $label; ?></a>
                <?php
            }
            ?>
    </div>

<?php
$this->includeAtTemplateBase('includes/footer.php');
