/*!     aweber_gutenberg_webform_block.js
 *
 *     Create custom AWeber Signup form block in the Gutenberg Editor in wordpress.
 */

const { __ } = wp.i18n;

// Import registerBlockType() from wp.blocks
const { registerBlockType } = wp.blocks;

// wp.element is Gutenberg's thin abstraction layer atop React
const { Component, Fragment } = wp.element;

// Import the AWeber ICONS
import icons from './icons/icons.js'

// Import the Select Component to create the Select Dropdown with the Signup forms and Save Button.
import AWeberSelectClass from './components/select_component.js'

// Import the Stylesheet to style the AWeber Block
import './../css/aweber_gutenberg_webform_block.css'

// Register the AWeber Sign up form Block with Gutenberg.
registerBlockType( 'aweber-signupform-block/aweber-shortcode', {
    title: __( 'Add an AWeber Sign Up Form' ),
    icon: icons.aweber,
    category: 'common',
    attributes: {
        selectedShortCode: {
            type: 'string',
            default: '',
        }
    },
    edit: function(props) {
        // Create a HTML Image tag.
        function ImageTag(props) {
            return (
                <img src={props.srcattr} />
            );
        }

        // Removes the selected shortcode from attributes.
        function removeSelectedShortCode(event) {
            props.setAttributes({
                selectedShortCode: ''
            })
        }

        // If the user has not selected the signup, then it will show the Dropdown with the list of Signup Forms.
        if (props.attributes.selectedShortCode == ''){
            return (
                <div className="aweber-placeholder">
                    <div className="aweber-placeholer-label">
                        <span className="aweber-placeholder-image">
                            <ImageTag srcattr={gutenberg_php_vars.aweber_logo} />
                        </span>
                        <span className="aweber-placeholder-text">Add an AWeber Sign Up Form</span>
                    </div>
                    <div className="aweber-placeholder-content">
                        <AWeberSelectClass setAttributes={props.setAttributes} {...props} />
                    </div>
                </div>
            );
        }

        // If user has selected the Signup Form, Show the Shortcode.
        var shortcodeSeperated = props.attributes.selectedShortCode.split('-');
        return (
            <div className="aweber-placeholder">
                <div className="aweber-placeholer-label">
                    <span className="aweber-placeholder-image">
                        <ImageTag srcattr={gutenberg_php_vars.aweber_logo} />
                    </span>
                    <span className="aweber-placeholder-text">Add an AWeber Sign Up Form</span>
                </div>
                <div className="aweber-placeholder-content">
                    <label className="aweber-shortcode-label">
                        Shortcode:
                    </label>

                    <label className="aweber-shortcode-label-content">
                        [aweber listid={shortcodeSeperated[0]} formid={shortcodeSeperated[1]} formtype={shortcodeSeperated[2]}]
                    </label>

                    <br />

                    <button type="button" onClick={removeSelectedShortCode} className="aweber-button aweber-small-btn">Edit</button>
                </div>
            </div>
        );
    },
    save: function(props) {
        // This function is executed, Once the Wordpress homepage loads.
        var shortcodeSeperated = props.attributes.selectedShortCode;
        
        // If the Signup Form not selected. then just return empty Div.
        if (shortcodeSeperated === '') {
            return <div />;
        }

        // Split the shortcode and return the HTML.
        shortcodeSeperated = shortcodeSeperated.split('-');
        return (
            <div>
                [aweber listid={shortcodeSeperated[0]} formid={shortcodeSeperated[1]} formtype={shortcodeSeperated[2]}]
            </div>
        );
    },
});
