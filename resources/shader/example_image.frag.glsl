#version 330 core

in vec2 v_uv;
in float frame;

uniform sampler2D u_sprite;
uniform int u_rows;
uniform int u_cols;

out vec4 fragment_color;

void main()
{
    int row_frame_count = u_rows;
    int col_frame_count = u_cols;

    vec2 frame_size = vec2(1.0 / row_frame_count, 1.0 / col_frame_count);

    // determine the position of the current
    int frameX = int(mod(frame, row_frame_count));
    int frameY = int(frame / row_frame_count);
    vec2 frame_coords = vec2(frameX * frame_size.x, frameY * frame_size.y);

    // calculate the uv
    vec2 uv = frame_coords + v_uv * frame_size;

    fragment_color = texture(u_sprite, uv);
}