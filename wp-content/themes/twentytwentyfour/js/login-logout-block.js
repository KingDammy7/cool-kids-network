const { registerBlockType } = wp.blocks;
const { useBlockProps } = wp.blockEditor;

registerBlockType("my-theme/login-logout-button", {
  title: "Login/Logout Button",
  icon: "admin-users",
  category: "common",

  edit: function (props) {
    const blockProps = useBlockProps();

    return wp.element.createElement(
      "div",
      blockProps,
      "Login/Logout Button (Dynamic)"
    );
  },

  save: function () {
    return null; // Dynamic block, render handled by PHP
  },
});
