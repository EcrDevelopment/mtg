<div>
    <div class="sm:px-6 w-full pt-12 pb-4">
        <x-custom-table>
            <x-slot name="titulo">
                <h2 class="text-indigo-600 font-bold text-3xl uppercase">
                    <i class="fa-solid fa-file-circle-check fa-xl text-indigo-600"></i>
                    &nbsp;Listado de Chips
                </h2>
            </x-slot>
            <x-slot name="btnAgregar" class="mt-6 ">
            </x-slot>
            <x-slot name="contenido">
                @if ($chipsConsumidos->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Inspector
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Servicio
                                </th>
                                {{-- para agregar placa o id
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Placa / Id
                                </th>--}}
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ubicación
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Grupo
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($chipsConsumidos as $key => $chip)
                                <tr>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <div class="flex items-center">
                                            <p class="text-indigo-900 p-1 bg-indigo-200 rounded-md">
                                                {{ $chip->id }}{{--{{$key + 1}}--}}
                                                
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $chip->nombreInspector }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if (Str::startsWith($chip->ubicacion, 'En poder del cliente '))
                                                <p class="text-sm leading-none text-gray-600 ml-2 p-2 bg-blue-200 rounded-full">
                                                    Chip por deterioro
                                                </p> 
                                            @else
                                                <p class="text-sm leading-none text-gray-600 ml-2 p-2 bg-green-200 rounded-full">
                                                    Conversión a GNV + chip
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                    {{-- para agregar placa o id
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{$chip->id}}
                                    </td>
                                    --}}
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($chip->estado == 4)
                                            Consumido
                                        @else
                                            {{ $chip->estado }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $chip->ubicacion }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $chip->grupo }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($chip->updated_at)->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No hay chips consumidos por inspectores.</p>
                @endif
            </x-slot>
        </x-custom-table>
    </div>
</div>
