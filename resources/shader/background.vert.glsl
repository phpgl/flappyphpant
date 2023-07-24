#version 330 core

layout (location = 0) in vec3 a_pos;
layout (location = 1) in vec2 a_uv;

out vec2 v_uv;

uniform mat4 u_view;
uniform mat4 u_projection;
uniform mat4 u_model;

void main() {
    v_uv = a_uv; // pass the texture coordinates to the fragment shader
    gl_Position = u_projection * u_view * u_model * vec4(a_pos, 1.0);
}