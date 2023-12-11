# This requires sass to be installed; see: https://sass-lang.com/install/
# This takes two arguments, the input directory and output directory, defaults
# to /scss and /css. This should work without arguments on most OP themes if
# run from theme directory

function watchsass() {
  # Default input and output directories.
  input_directory="${1:-./scss}"
  output_directory="${2:-./css}"

  # Validate input directory exists.
  if [ ! -d "$input_directory" ]; then
    echo "Input directory '$input_directory' does not exist."
    return 1
  fi

  # Validate output directory exists.
  if [ ! -d "$output_directory" ]; then
    echo "Output directory '$output_directory' does not exist."
    return 1
  fi

  # Compile Sass, stop on error, no source map.
  sass --watch --stop-on-error "$input_directory":"$output_directory" --no-source-map
}

watchsass
