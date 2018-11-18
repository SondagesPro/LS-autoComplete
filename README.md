# autoComplete : allow to use an external XSV for short text question with CSV. #

## Installation

This plugin was tested on LimeSurvey 2.73 and 3.15 version, must work on all version after 2.50.

### Via GIT
- Go to your LimeSurvey Directory
- Clone in plugins/autoComplete directory : `git clone https://gitlab.com/SondagesPro/QuestionSettingsType/autoComplete.git autoComplete`

### Via ZIP dowload
- Get the file [autoComplete.zip](https://extensions.sondages.pro/IMG/auto/autoComplete.zip)
- Extract : `unzip autoComplete.zip`
- Move the directory to plugins/ directory inside LimeSurvey

### Activate

You just need to activate plugin like other plugin, see [Install and activate a plugin for LimeSurvey](https://extensions.sondages.pro/install-and-activate-a-plugin-for-limesurvey.html).

## Usage
- Create a short text question, look at advanced settings and open AutoComplete panel
- _autoComplete_: Activate this plugin for this question
- _autoCompleteCsvFile_: CSV file to be used, **attention** this csv file must be uploaded in ressources files of the survey. You can use default _Survey menu_ / _Resources_ or the HTML editor : _Insert link_ / _Browse server_.
- _autoCompleteOneColumn_: If you use only the 1st column of the csv file : data and value are in this column. If you use 2 column : data fill the answer, but value are shown to the user.
- _autoCompleteFilter_: Adding an extra filter to the returned value. Returned value are filtered by the current value : data (code) must start by this value. The value use [Expression Manager](https://manual.limesurvey.org/Expression_Manager).
- _autoCompleteMinChar_: Minimum character to start search.
- _autoCompleteRemoveSpecialChar_: Remove special character and do the search in lower case (for value) (this function need testing).
- _autoCompleteAsDropdown_: Disable javascript search system and show autocomplete as dropdown. This disable the _autoCompleteMinChar_ setting.

**Attention** With big CSV file : return value can be very long.

## Contribute

Issue and pull request are welcome on [gitlab](https://gitlab.com/SondagesPro/QuestionSettingsType/autoComplete).

Translation are managed on [translate.sondages.pro](https://translate.sondages.pro/projects/autocomplete/), you must register before update string.
If you language is not available, open a issue on [gitlab](https://gitlab.com/SondagesPro/QuestionSettingsType/autoComplete).

## Home page & Copyright

- HomePage <http://extensions.sondages.pro/>
- Copyright © 2017-2018 Denis Chenu <http://sondages.pro>
- Licence : GNU Affero General Public License <https://www.gnu.org/licenses/agpl-3.0.html>
