#version 330 core

layout (location = 0) in vec2 a_pos;
layout (location = 1) in vec2 a_uv;
layout (location = 2) in mat4 a_model;
layout (location = 6) in float a_frame;

uniform mat4 u_view;
uniform mat4 u_projection;
uniform vec2 u_resolution;

out vec2 v_uv;
out float frame;

void main()
{
    // forward the uv and frame to the fragment shader
    v_uv = a_uv;
    frame = a_frame;

    // calculate the final screenspace position
    gl_Position = u_projection * u_view * a_model * vec4(a_pos, 0.0, 1.0);
}