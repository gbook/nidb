<?php

/**
 * ----------------------------------------------------------------------
 *  
 * Copyright (c) 2012 Khaled Al-Shamaa.
 *  
 * http://www.ar-php.org
 *  
 * PHP Version 5 
 *  
 * ----------------------------------------------------------------------
 * @desc      We aim in Al-Kashi project to provide a rich package full of statistical 
 *            functions useful for online business intelligent and data mining, possible 
 *            applications may include an online log file analysis, Ad's and Campaign 
 *            statistics, or survey/voting results on-fly analysis. 
 *          
 * @category  Math 
 * @package   Kashi
 * @author    Khaled Al-Shamaa <khaled.alshamaa@gmail.com>
 * @copyright 2012 Khaled Al-Shamaa
 *    
 * @license   GPL <http://www.gnu.org/licenses/gpl.txt>
 * @version   1.0.0 released in March 5, 2012
 * @link      http://www.ar-php.org/stats/al-kashi
 */

class Kashi {

    private $values = array();
	private $dataset = array();
	
    public function __construct() {
    }
	
	public function __destruct() {
	}
    
    /**
     * Compute the arithmetic mean, and is calculated by adding a group of numbers 
	 * and then dividing by the count of those numbers. 
	 * For example, the mean of 2, 3, 3, 5, 7, and 10 is 30 divided by 6, which is 5. 
     *          
     * @param array List of float values for which you want to calculate the mean.
     *                    
     * @return float Arithmetic mean
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function mean() {
        if (func_num_args() > 0) {
            $this->values = func_get_arg(0);
        }
        
        $total = 0;
        
        foreach ($this->values as $value) {
            $total += $value;
        }
        
        return $total/count($this->values);
    }
    
    /**
     * Compute the sample median which is the middle number of a group of numbers; that is, 
	 * half the numbers have values that are greater than the median, and half the numbers 
	 * have values that are less than the median. 
	 * For example, the median of 2, 3, 3, 5, 7, and 10 is 4.
     *          
     * @param array List of float values for which you want to calculate the median.
     *                    
     * @return float Sample median
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function median() {
        if (func_num_args() > 0) {
            $this->values = func_get_arg(0);
        }

        $list = $this->values;
        
        sort($list);
        
        $count = count($list);
        
        if ($count % 2 == 0) {
            $median = ($list[($count / 2) - 1] + $list[$count / 2]) / 2;
        } else {
            $median = $list[floor($count / 2)];
        }
        
        return $median;
    }

    /**
     * Compute the mode which is the most frequently occurring number in a group of numbers. 
	 * For example, the mode of 2, 3, 3, 5, 7, and 10 is 3.
     *          
     * @param array List of float values for which you want to calculate the mode.
     *                    
     * @return float Returns the most frequently occurring or repetitive value
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function mode() {
        if (func_num_args() > 0) {
            $this->values = func_get_arg(0);
        }

        $counter = array();
        
        foreach ($this->values as $value) {
            if (isset($counter[$value])) {
                $counter[$value]++;
            } else {
                $counter[$value] = 1;
            }
        }
        
        return array_keys($counter, max($counter));
    }
    
    /**
     * Estimates variance based on a sample
     *          
     * @param array List of float values corresponding to a sample of a population.
     *                    
     * @return float Returns the sample variance
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function variance() {
        if (func_num_args() > 0) {
            $this->values = func_get_arg(0);
        }

        $mean = $this->mean();
       
        $var = 0;
        
        foreach ($this->values as $value) {
            $var += ($value - $mean) * ($value - $mean);
        }
        
        $var = $var / (count($this->values) - 1);
        
        return $var;
    }
    
    /**
     * Compute the standard deviation based on a sample. The standard deviation is a measure of 
	 * how widely values are dispersed from the average value (the mean). 
     *          
     * @param array List of float values for which you want to calculate the standard deviation.
     *                    
     * @return float Returns the standard deviation
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function sd() {
        if (func_num_args() > 0) {
            $this->values = func_get_arg(0);
        }

        return sqrt($this->variance());
    }
    
    /**
     * Compute the skewness of a distribution. Skewness characterizes the degree of asymmetry of 
	 * a distribution around its mean. Positive skewness indicates a distribution with an asymmetric 
	 * tail extending toward more positive values. Negative skewness indicates a distribution with 
	 * an asymmetric tail extending toward more negative values.
     *          
     * @param array List of float values for which you want to calculate the skewness.
     *                    
     * @return float Returns the skewness of a distribution
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function skew() {
        if (func_num_args() > 0) {
            $this->values = func_get_arg(0);
        }

        $mean = $this->mean();
        $sd   = $this->sd();
        $n    = count($this->values);
       
        $skew = 0;
        
        foreach ($this->values as $value) {
            $skew += pow(($value - $mean) / $sd, 3);
        }

        $skew = ($skew * $n) / (($n - 1) * ($n - 2));
        
        // skew standard error = sqrt(6 / $n)
        // skew is significant if its value exceed 2 * sqrt(6 / $n)
        
        return $skew;
    }
    
    /**
     * Compute the kurtosis of a distribution. Kurtosis characterizes the relative peakedness or 
	 * flatness of a distribution compared with the normal distribution. Positive kurtosis indicates 
	 * a relatively peaked distribution. Negative kurtosis indicates a relatively flat distribution.
     *          
     * @param array List of float values for which you want to calculate the kurtosis.
     *                    
     * @return float Returns the kurtosis of a distribution
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function kurt() {
        if (func_num_args() > 0) {
            $this->values = func_get_arg(0);
        }

        $mean = $this->mean();
        $sd   = $this->sd();
        $n    = count($this->values);
       
        $kurt = 0;
        
        foreach ($this->values as $value) {
            $kurt += pow(($value - $mean) / $sd, 4);
        }
        
        $kurt = ($kurt * $n * ($n + 1)) / (($n - 1) * ($n - 2) * ($n - 3));
        $kurt = $kurt - ((3 * ($n - 1) * ($n - 1)) / (($n - 2) * ($n - 3)));
        
        // kurt standard error = sqrt(24 / $n)
        // kurt is significant if its value exceed 2 * sqrt(24 / $n)
        
        return $kurt;
    }
    
    /**
	 * Compute the coefficients of variation 
     *          
     * @param array List of float values for which you want to calculate the coefficients of variation.
     *                    
     * @return float Returns the coefficients of variation
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
	 */
	public function cv() {
        if (func_num_args() > 0) {
            $this->values = func_get_arg(0);
        }

        $mean = $this->mean();
        $sd   = $this->sd();
    
        $cv = ($sd / $mean) * 100;
        
        return $cv;
    }
    
    /**
     * Compute the covariance, the average of the products of deviations for each data point pair. 
	 * Use covariance to determine the relationship between two data sets. For example, you can 
	 * examine whether greater income accompanies greater levels of education.
     *          
     * @param array $x First list of float values
     * @param array $y Second list of float values
     *                    
     * @return float Returns the covariance
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function cov($x, $y) {
        $meanX = $this->mean($x);
        $meanY = $this->mean($y);
        
        $count = count($x);
        $total = 0;
        
        for ($i=0; $i<$count; $i++) {
            $total += ($x[$i] - $meanX) * ($y[$i] - $meanY);
        }
        
        return (1 / ($count - 1)) * $total;
    }
    
    /**
     * Compute the correlation coefficient. Use the correlation coefficient to determine the 
	 * relationship between two properties. It uses different measures of association, all 
	 * in the range [-1, 1] with 0 indicating no association.
     *          
     * @param array $x First list of float values
     * @param array $y Second list of float values
     *                    
     * @return float Returns the correlation coefficient 
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function cor($x, $y) {
        $cov = $this->cov($x, $y);
        $sdX = $this->sd($x);
        $sdY = $this->sd($y);
        
        return $cov / ($sdX * $sdY);
    }
	
    /**
     * Test of the null hypothesis that true correlation is equal to 0
     *          
     * @param array $r Correlation value
     * @param array $n Number of observations
     *                    
     * @return float Returns null hypothesis probability: true correlation is equal to 0 
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
	public function corTest($r, $n) {
		$t = $r / sqrt((1 - ($r * $r)) / ($n - 2));
		
		$result = $this->tDist($t, $n - 2);
		
		return $result;
	}
    
    /**
     * Compute the simple linear regression fits a linear model to represent 
	 * the relationship between a response (or y-) variate, and an explanatory
     * (or x-) variate. 
     *          
     * @param array $x Specifies the name of the explanatory (or x-) variate.
     * @param array $y Specifies the name of the response (or y-) variate.
     *                    
     * @return array Returns [intercept], [sploe], and [r-square] as float values
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function lm($x, $y) {
        $meanX = $this->mean($x);
        $meanY = $this->mean($y);
        
        $n     = count($x);
        
        $nominator   = 0;
        $denominator = 0;
        
        for ($i=0; $i<$n; $i++) {
            $nominator   += ($x[$i] - $meanX) * ($y[$i] - $meanY);
            $denominator += ($x[$i] - $meanX) * ($x[$i] - $meanX);
        }
        
        $b = $nominator / $denominator;
        $a = $meanY - $b * $meanX;
        
        // Total sum of squares (ss) and Residual sum of squares (rss)
        $ss  = 0;
        $rss = 0;
        
        for ($i=0; $i<$n; $i++) {
            $ss  += pow($y[$i] - $meanY, 2);

            $est  = $a + ($b * $x[$i]);
            $rss += pow($y[$i] - $est, 2);
        }
        
        // R-square value
        $r2 = 1 - ($rss / $ss);
        
        return array('intercept'=>$a, 'slope'=>$b, 'r-square'=>$r2);
    }
    
    /**
     * Compute the Student's t-Test value to determine whether two samples are likely 
	 * to have come from the same two underlying populations that have the same mean
     *          
     * @param array $a First list of float values
     * @param array $b Second list of float values
     * @param boolean $paired Logical indicating whether you want a paired t-test
     *                    
     * @return float Returns the associated with a Student's t-Test 
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function tTest ($a, $b, $paired=false) {
        if ($paired == true) {
            $count = count($a);
            
            $diff = array();
            
            for ($i=0; $i<$count; $i++) {
                $diff[$i] = $a[$i] - $b[$i];
            }
            
            $mean = $this->mean($diff);
            $var  = $this->variance($diff);
            
            $t = $mean / sqrt($var / $count);
        } else {
            $meanA = $this->mean($a);
            $meanB = $this->mean($b);
            
            $varA = $this->variance($a);
            $varB = $this->variance($b);
            
            $countA = count($a);
            $countB = count($b);
            
            $t = ($meanA - $meanB) / sqrt(($varA / $countA) + ($varB / $countB));
        }
        
        return $t;
    }
    
    /**
     * Returns the standard normal cumulative distribution function. The distribution 
	 * has a mean of 0 (zero) and a standard deviation of one. Use this function in 
	 * place of a table of standard normal curve areas.
     *          
     * @param float $x    Is the value for which you want the distribution.
     * @param float $mean The distribution mean (default is zero).
     * @param float $sd   The distribution standard deviation (default is one).
     *                    
     * @return float the standard normal cumulative distribution function. 
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function norm ($x, $mean=0, $sd=1) {
        $y = (1 / sqrt(2 * pi())) * exp(-0.5 * pow($x, 2));
        
        return $y;
    }
	
	private function _zip ($q, $i, $j, $b) {
		$zz = 1;
		$z  = $zz;
		$k  = $i;
		
		while ($k <= $j) {
			$zz *= $q * $k / ($k - $b);
			$z  += $zz;
			$k  += 2;
		}
		
		return $z;
	}
    
    /**
     * Returns the Percentage Points (probability) for the Student t-distribution 
	 * where a numeric value (t) is a calculated value of t for which the Percentage 
	 * Points are to be computed.
     *          
     * @param float   $t    Is the numeric value at which to evaluate the distribution.
     * @param integer $n    Is an integer indicating the number of degrees of freedom.
     * @param integer $tail Specifies the number of distribution tails to return.
	 *                      If tail = 1, TDIST returns the one-tailed distribution.
	 *                      If tail = 2, TDIST returns the two-tailed distribution.
     *                    
     * @return float the Percentage Points (probability) for the Student t-distribution 
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
	public function tDist ($t, $n, $tail=1) {
		$pj2 = pi() / 2;
		
		$t = abs($t);
		
		$rt = $t / sqrt($n);
		$fk = atan($rt);
		
		if ($n == 1) {
			$result = 1 - $fk / $pj2;
		} else {
			$ek = sin($fk);
			$dk = cos($fk);
			
			if (($n % 2) == 1) {
				$result = 1 - ($fk + $ek * $dk * $this->_zip($dk * $dk, 2, $n-3, -1)) / $pj2;
			} else {
				$result = 1 - $ek * $this->_zip($dk * $dk, 1, $n-3, -1);
			}
		}
		
		return $result / $tail;
	}
	
    /**
     * Returns the F probability distribution. You can use this function to determine 
	 * whether two data sets have different degrees of diversity.
     *          
     * @param float   $f   Is the value at which to evaluate the function.
     * @param integer $df1 Is the numerator degrees of freedom.
     * @param integer $df2 Is the denominator degrees of freedom.
     *                    
     * @return float the F probability distribution 
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
	public function fDist ($f, $df1, $df2) {
		$pj2 = pi() / 2;
		
		$x = $df2 / ($df1 * $f + $df2);
		
		if (($df1 % 2) == 0) {
			return $this->_zip(1 - $x, $df2, $df1 + $df2 - 4, $df2 - 2) * pow($x, $df2 / 2);
		}
		
		if (($df2 % 2) == 0) {
			return 1 - $this->_zip($x, $df1, $df1 + $df2 - 4, $df1 - 2) * pow(1 - $x, $df1 / 2);
		}
		
		$tan = atan(sqrt($df1 * $f / $df2));
		$a   = $tan / $pj2;
		$sat = sin($tan);
		$cot = cos($tan);
		
		if ($df2 > 1) {
			$a = $a + $sat * $cot * $this->_zip($cot * $cot, 2, $df2 - 3, -1) / $pj2;
		}
		
		if ($df1 == 1) {
			return 1 - $a;
		}
		
		$c = 4 * $this->_zip($sat * $sat, $df2 + 1, $df1 + $df2 - 4, $df2 - 2) * $sat * pow($cot, $df2) / pi();
		
		if ($df2 == 1) {
			return 1 - $a + $c / 2;
		}
		
		$k = 2;
		
		while ($k <= ($df2 - 1) / 2) {
			$c *= $k / ($k - 0.5);
			$k++;
		}
		
		return 1 - $a + $c;
	}
	
    /**
     * Return the probability of normal z value
     * Adapted from a polynomial approximation in:
     *     Ibbetson D, Algorithm 209
     *     Collected Algorithms of the CACM 1963 p. 616
     *          
     * @param float $z Is the value at which you want to evaluate the probability.
     *                    
     * @return float the probability of normal z value 
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function normCDF ($z) {
        $max = 6;
        
        if ($z == 0) {
            $x = 0;
        } else {
            $y = abs($z) / 2;
            if ($y >= ($max / 2)) {
                $x = 1;
            } else if ($y < 1) {
                $w = $y * $y;
                $x = ((((((((0.000124818987 * $w
                          - 0.001075204047) * $w + 0.005198775019) * $w
                          - 0.019198292004) * $w + 0.059054035642) * $w
                          - 0.151968751364) * $w + 0.319152932694) * $w
                          - 0.531923007300) * $w + 0.797884560593) * $y * 2;
            } else {
                $y -= 2;
                $x = (((((((((((((-0.000045255659 * $y
                                + 0.000152529290) * $y - 0.000019538132) * $y
                                - 0.000676904986) * $y + 0.001390604284) * $y
                                - 0.000794620820) * $y - 0.002034254874) * $y
                                + 0.006549791214) * $y - 0.010557625006) * $y
                                + 0.011630447319) * $y - 0.009279453341) * $y
                                + 0.005353579108) * $y - 0.002141268741) * $y
                                + 0.000535310849) * $y + 0.999936657524;
            }
        }
        
        if ($z > 0) {
            $result = ($x + 1) / 2;
        } else {
            $result = (1 - $x) / 2;
        }
        
        return $result;
    }
    
    /**
     * Returns the one-tailed probability of the chi-squared distribution. The chi-squared 
	 * distribution is associated with a chi-squared test. Use the chi-squared test to 
	 * compare observed and expected values. 
     * Adapted from:
     *     Hill, I. D. and Pike, M. C.  Algorithm 299
     *     Collected Algorithms for the CACM 1967 p. 243
     * Updated for rounding errors based on remark in
     *     ACM TOMS June 1985, page 185
     *          
     * @param float   $x  Is the value at which you want to evaluate the distribution.
     * @param integer $df Is the number of degrees of freedom.
     *                    
     * @return float the probability of the chi-squared distribution 
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
	public function chiDist ($x, $df) {
        define(LOG_SQRT_PI, log(sqrt(pi())));
        define(I_SQRT_PI, 1 / sqrt(pi()));
        
        if ($x <= 0 || $df < 1) {
            $result = 1;
        }
        
        $a = $x / 2;
        
        if ($df % 2 == 0) {
            $even = true;
        } else {
            $even = false;
        }

        if ($df > 1) {
            $y = exp(-1 * $a);
        }
        
        if ($even) {
            $s = $y;
        } else {
            $s = 2 * $this->normCDF(-1 * sqrt($x));
        }

        if ($df > 2) {
            $x = ($df - 1) / 2;
            
            if ($even) {
                $z = 1;
            } else {
                $z = 0.5;
            }
            
            if ($a > 20) {
                if ($even) {
                    $e = 0;
                } else {
                    $e = LOG_SQRT_PI;
                }

                $c = log($a);

                while ($z <= $x) {
                    $e += log($z);
                    $s += exp($c * $z - $a - $e);
                    $z += 1;
                }
                
                $result = $s;
            } else {
                if ($even) {
                    $e = 1;
                } else {
                    $e = I_SQRT_PI / sqrt($a);
                }

                $c = 0;
                
                while ($z <= $x) {
                    $e *= ($a / $z);
                    $c += $e;
                    $z += 1;
                }
                
                $result = $c * $y + $s;
            }
        } else {
            $result = $s;
        }

        return $result;
	}

    /**
     * Performs chi-squared contingency table tests and goodness-of-fit tests. 
	 *
	 * Example:
	 * <code>
	 * $table['Automatic'] = array('4 Cylinders' => 3, '6 Cylinders' => 4, '8 Cylinders' => 12);
	 * $table['Manual']    = array('4 Cylinders' => 8, '6 Cylinders' => 3, '8 Cylinders' => 2);
	 *
	 * $results = $stats->chiTest($table);
	 * </code>
     *          
     * @param array $table Specifies the two-way, n x m table containing the counts 
     *                    
     * @return array [chi] => the value the chi-squared test statistic
	 *               [df] => the degrees of freedom of the approximate chi-squared distribution of the test statistic.
	 *               [expected] => the expected counts under the null hypothesis.
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
	public function chiTest ($table) {
		$total = 0;
		$chi   = 0;
		
		foreach ($table as $category => $row) {
			foreach ($row as $sample => $cell) {
				if (isset($rows[$category])) {
					$rows[$category] += $cell;
				} else {
					$rows[$category] = $cell;
				}
				
				if (isset($cols[$sample])) {
					$cols[$sample] += $cell;
				} else {
					$cols[$sample] = $cell;
				}
				
				$total += $cell;
			}
		}
		
		$r  = count($rows);
		$c  = count($cols);
		$df = ($r - 1) * ($c - 1);

		$expected = array();
		
		foreach ($table as $category => $row) {
			foreach ($row as $sample => $cell) {
				// fo frequency of the observed value
				// fe frequency of the expected value
				$fo  = $cell;
				$fe  = ($rows[$category] * $cols[$sample]) / $total;
				$chi += pow($fo - $fe, 2) / $fe;
				
				$expected[$category][$sample] = $fe;
			}
		}
		
		return array('chi'=>$chi, 'df'=>$df, 'expected'=>$expected);
	}
	
    /**
     * Returns the Shannon diversity index value
     *          
     * @param array   $abundances Associated array where keys refer to the categories and values refer to the observation counts in each category.
     * @param string  $index      Which diversity index you would like to calculate (currently only "shannon" index supported)
     * @param float   $base       Base of the logarithmic transformation used in the Shannon diversity index (default is M_E)
     *                    
     * @return float The shannon diversity index
     * @author Khaled Al-Sham'aa <khaled.alshamaa@gmail.com>
     */
    public function diversity ($abundances, $index='shannon', $base=M_E) {
        $index = strtolower($index);
        $N = array_sum($abundances);
        
        // Calculate the proportion of characters belonging to each category
        foreach ($abundances as $key=>$value) {
            $P["$key"] = $value / $N;
        }
        
        $result = 0;
        
        if ($index == 'shannon') {
            foreach ($P as $key=>$value) {
                $result += $value * log($value, $base);
            }
            $result = -1 * $result;
        }
        
        return $result;
    }
}
