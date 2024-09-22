/*!     select_component.js
 *
 *     Return the Select Dropdown with the List of Signup forms and Save button.
 */

// wp.element is Gutenberg's thin abstraction layer atop React
const { Component, Fragment } = wp.element;

export default class AWeberSelectClass extends Component {
    constructor(props) {
        super(props);

        // Set the state of the class.
        this.state = {
            isLoaded: false,
            error: false,
            selectedShortCode: '',
            errorMessage: '',
            errorClassName: '',
            message: 'Please wait loading the shortcodes.'
        };

        // Bind the ON change event.
        this.shortcodeSelected = this.shortcodeSelected.bind(this);
        this.saveClicked = this.saveClicked.bind(this);
        this.createMarkup = this.createMarkup.bind(this);
    };

    componentDidMount() {
        fetch(ajaxurl + '?action=get_aweber_webform_shortcodes&nonce=' + gutenberg_php_vars.aweber_nonce.get_aweber_webform_shortcodes)
            .then(response => response.json())
            .then( 
                (result) => {
                    status = result.status;
                    if (status === 'error'){
                        this.setState({
                            isLoaded: true,
                            error: true,
                            errorClassName: 'aweber-block-error',
                            message: result.message
                        });
                    } else {
                        let shortcodes = [];
                        let options = [];
                        let currentListId = result.short_codes[0].list_id;
                        let currentListName = result.short_codes[0].list_name;
                        for(let i=0; i < result.short_codes.length; i++) {
                            if (currentListId != result.short_codes[i].list_id) {
                                shortcodes.push({
                                    list_id: currentListId,
                                    list_name: currentListName,
                                    options: options
                                });
                                options = [];
                                currentListId = result.short_codes[i].list_id;
                                currentListName = result.short_codes[i].list_name;
                            }
                            options.push({'name': result.short_codes[i].text, 'value': result.short_codes[i].value});
                        }
                        if (options.length > 0) {
                            shortcodes.push({
                                'list_id': currentListId,
                                'list_name': currentListName,
                                'options': options
                            });
                        }

                        this.setState({
                            isLoaded: true,
                            error: false,
                            errorClassName: '',
                            shortcodes: shortcodes
                        });
                    }
                },
                (error)  => {
                    this.setState({
                        isLoaded: true,
                        error: true,
                        errorClassName: '',
                        message: 'Something went wrong. Please try again.'
                    });
                }
            );
    };

    shortcodeSelected(event)  {
        this.setState({
            errorClassName: '',
            selectedShortCode: event.target.value
        });
    };

    saveClicked (event) {
        if (this.state.selectedShortCode === '') {
            this.setState({
                errorClassName: '',
                errorMessage: 'Please select the Sign Up Form'
            });
            return;
        }

        const { setAttributes } = this.props;
        setAttributes({
            selectedShortCode: this.state.selectedShortCode
        });
    };

    createMarkup() {
        // If the error occurred in the backend. the response comes in the HTML format. To render the HTML it, used dangerouslySetInnerHTML
        return {__html: this.state.message};
    }

    render() {
        if (this.state.isLoaded && !this.state.error) {
            const shortcodes = this.state.shortcodes;
            return (
                <>
                    <select className="aweber-dropdown" value={this.state.selectedShortCode} onChange={this.shortcodeSelected}>
                        <option value="">Please select a Sign Up Form</option>
                        {
                            shortcodes.map((shortcode, listkey) => {
                                return (
                                        <optgroup key={listkey} label={shortcode.list_name}>
                                            {
                                                shortcode.options.map((option, formkey) => {
                                                    return <option key={formkey} value={option.value}>{option.name}</option>
                                                })
                                            }
                                        </optgroup>
                                    );
                            })
                        }
                    </select>
                    <button type="button" className="aweber-button" onClick={this.saveClicked}>Save</button>
                    <span className="aweber-error-label">{this.state.errorMessage}</span>
                </>
            );
        }
        return (
            <div className={this.state.errorClassName} dangerouslySetInnerHTML={this.createMarkup()} />
        );
    };
};

