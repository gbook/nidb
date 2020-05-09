/*
*jQuery table2csv plugin 0.1.0
*Converts table html element to csv string
*Copyright (c) 2009 Leonardo Rossetti motw.leo@gmail.com
* Licensed under the MIT license (http://www.opensource.org/licenses/mit-license.php)
*THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
*IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
*FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
*AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
*LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
*OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
*THE SOFTWARE.
*/
//keeps closure
(function ($) { 
    $.fn.table2csv = function (options) {
        var defaults = {
            delimiter: ",",
            callback: function (csv) {
                return csv;
            }
        };
        var settings = $.extend(defaults, options);
    
        return this.each(function () {
            var csv = "";
            //gets th to set column headers
            $(this).find("thead tr th").each(function() {
                csv += "\"" + $(this).text().trim().replace(/(\")/gim, "\\\"\\\"") + "\"" + settings.delimiter; 
            });
            csv += "\n";
            //each td as a csv column
            $(this).find("tbody tr").each(function () {
                $(this).find("td").each(function () {
                    csv += "\"" + $(this).text().trim().replace(/(\")/gim, "\\\"\\\"") + "\"" + settings.delimiter;
                });
                csv += "\n";
            });
            //callback function containing csv string as parameter
            settings.callback(csv);
        });
    }
})(jQuery);