<?php
/* @var $line int|null */
/* @var $class string|null */
/* @var $lines string[] */
/* @var $handler \yii\web\ErrorHandler */
?>
<ul>
    <li class="call-stack-item application"
        data-line="<?= $line ?>">
        <div class="element-wrap">
            <div class="element">

                    <span class="call">
                        <?= $class ?>
                    </span>

            </div>
        </div>
		<?php if (!empty($lines)): ?>
            <div class="code-wrap">
                <div class="error-line"></div>
				<?php for ($i = 0; $i < count($lines); ++$i): ?><div class="hover-line"></div><?php endfor; ?>
                <div class="code">
					<?php for ($i = 0; $i < count($lines); ++$i): ?><span class="lines-item">&nbsp;</span><?php endfor; ?>
                    <pre><?php
						// fill empty lines with a whitespace to avoid rendering problems in opera
						for ($i = 0; $i < count($lines); ++$i)
							{
							echo (trim($lines[ $i ]) === '') ? " \n" : $handler->htmlEncode($lines[ $i ]);
							}
						?></pre>
                </div>
            </div>
		<?php endif; ?>
    </li>
    <ul>
