<?php
/**
 * The three project filters as selects: one value each, or "הכל" (spec v6 §8–9).
 * $args['projects']: the projects to count over. Counts are rewritten client-side as filters combine.
 */
foreach ( log_project_filters( $args['projects'] ?? log_public_projects() ) as $param => $f ) : ?>
	<label class="filters__group"><span><?php echo esc_html( $f['label'] ); ?></span>
		<select name="<?php echo esc_attr( $param ); ?>">
			<option value="">הכל</option>
			<?php foreach ( $f['options'] as $o ) : ?>
				<option value="<?php echo esc_attr( $o['value'] ); ?>" data-label="<?php echo esc_attr( $o['name'] ); ?>"><?php echo esc_html( $o['name'] ); ?> (<?php echo (int) $o['count']; ?>)</option>
			<?php endforeach; ?>
		</select>
	</label>
<?php endforeach;
