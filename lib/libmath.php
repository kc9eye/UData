<?php
/* This file is part of UData.
 * Copyright (C) 2018 Paul W. Lane <kc9eye@outlook.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/**
 * Calculates the percentage of 'quality' compared to 'control'
 * 
 * Returns the mathmatical percentage of `$quality` compared to 
 * `$control`. This precision of the number is determeined by the
 * optional argument `$precision`.
 * @param Float $quality The basis of the calculation.
 * @param Float $control The control to calculate the basis against
 * @param Integer $presicion The number of decimal points to return
 * @return Float The percentage representation of quality compared to control
 */
function qualityControlPercentage ($quality,$control,$precision = 2) {
    return (float) round((($quality/$control)*100),$precision,PHP_ROUND_HALF_UP);
}

/**
 * Determines if the number is non-zero
 * 
 * This function also test for if the number is in the negative range
 * @param Float $number
 * @return Boolean True if the number is not zero, false otherwise.
 */
function nonZeroNumber ($number) {
    if ($number <= 0) return false;
    return true;
}